/**
 * BicBucStriim - shared jsdom boot helpers for the tests/js harnesses
 *
 * Not a standalone test. The harnesses in this directory are driven by PHP
 * (see tests/Utilities/*ScriptTest.php): PHP renders the production page
 * markup, extracts the structure the script depends on, and passes it here as
 * JSON. This library boots a real jsdom DOM with that markup, runs the
 * production script into it and provides the XHR + assertion plumbing.
 *
 * jsdom is a dev dependency resolved through Yarn PnP, so harnesses must be
 * run via "yarn node", not plain "node":
 *
 *   yarn node tests/js/author_notes_harness.js <fixture.json> <project-root>
 *
 * Exits 0 when every assertion passes, 1 otherwise (via checker.finish()).
 */
(function () {
  'use strict';

  var fs = require('fs');
  var path = require('path');
  var jsdom = require('jsdom');

  /**
   * Read the shared <fixture.json> <project-root> CLI arguments.
   *
   * @param {string[]} argv process.argv
   * @return {object} parsed fixture
   */
  function readFixture(argv) {
    return JSON.parse(fs.readFileSync(argv[2], 'utf8'));
  }

  /**
   * ok/FAIL assertion bookkeeping with the shared exit-code contract.
   *
   * @param {string} label fixture label used in the FAIL line
   */
  function createChecker(label) {
    var failures = 0;
    return {
      check: function (name, cond) {
        console.log((cond ? '  ok   ' : '  FAIL ') + name);
        if (!cond) failures++;
      },
      /**
       * Print the harness-specific PASS line and exit. On failure the label and
       * assertion count are printed instead; the caller's exit code is what the
       * PHP driver asserts on, all output is captured for the failure report.
       *
       * @param {string} passLine printed verbatim when everything passed
       */
      finish: function (passLine) {
        if (failures === 0) {
          console.log(passLine);
          process.exit(0);
        }
        console.log('FAIL ' + label + ' - ' + failures + ' assertion(s) failed');
        process.exit(1);
      }
    };
  }

  /**
   * XMLHttpRequest fake that records requests instead of hitting the network.
   * respond() completes synchronously, like the previous stub harnesses did.
   */
  function createXhrStub() {
    var log = [];

    function FakeXHR() {
      this.readyState = 0;
      this.status = 0;
      this.responseText = '';
      this.headers = {};
      log.push(this);
    }
    FakeXHR.prototype.open = function (type, url) {
      this.type = type;
      this.url = url;
    };
    FakeXHR.prototype.setRequestHeader = function (k, v) {
      this.headers[k] = v;
    };
    FakeXHR.prototype.send = function (body) {
      this.body = body;
    };
    FakeXHR.prototype.respond = function (status, body) {
      this.status = status;
      this.responseText = body;
      this.readyState = 4;
      this.onreadystatechange();
    };

    return {
      FakeXHR: FakeXHR,
      log: log,
      last: function () {
        return log[log.length - 1];
      }
    };
  }

  /**
   * Boot a jsdom window with production markup and run a production script
   * into it. Resolves once DOMContentLoaded has fired, i.e. after the script's
   * onReady callbacks ran (the script registers its listener during eval,
   * before the listener registered here).
   *
   * Handler exceptions inside jsdom are reported to the forwarded console but
   * do not propagate - the resulting state assertions are what fail the
   * harness, with the stack trace visible in the captured output.
   *
   * @param {string} root project root containing js/
   * @param {string} scriptRelPath script below js/, e.g. 'script.js'
   * @param {string} html production markup to run against
   * @return {Promise<object>} {window, document, xhr, error, fire, keydown}
   */
  function boot(root, scriptRelPath, html) {
    var vc = new jsdom.VirtualConsole();
    vc.forwardTo(console);

    var dom = new jsdom.JSDOM(html, {
      url: 'http://localhost/',
      runScripts: 'outside-only',
      virtualConsole: vc
    });
    var window = dom.window;
    var xhr = createXhrStub();
    // must be installed before eval: bare XMLHttpRequest inside window.eval
    // resolves against the window object
    window.XMLHttpRequest = xhr.FakeXHR;

    var error = null;
    try {
      window.eval(fs.readFileSync(path.join(root, 'js', scriptRelPath), 'utf8'));
    } catch (e) {
      error = e;
    }

    var env = {
      window: window,
      document: window.document,
      xhr: xhr,
      error: error,
      /**
       * Dispatch a cancelable bubbling event like a user would.
       *
       * @return {boolean} true when the production handler called
       *         preventDefault() (event.defaultPrevented)
       */
      fire: function (el, type) {
        var ev = new window.Event(type, { bubbles: true, cancelable: true });
        el.dispatchEvent(ev);
        return ev.defaultPrevented;
      },
      /** Dispatch a keydown, e.g. keydown('Escape'). */
      keydown: function (key) {
        window.document.dispatchEvent(new window.KeyboardEvent('keydown', { key: key }));
      }
    };

    return new Promise(function (resolve) {
      if (window.document.readyState !== 'loading') {
        resolve(env);
        return;
      }
      window.document.addEventListener('DOMContentLoaded', function () {
        resolve(env);
      });
    });
  }

  module.exports = {
    readFixture: readFixture,
    createChecker: createChecker,
    createXhrStub: createXhrStub,
    boot: boot
  };
})();
