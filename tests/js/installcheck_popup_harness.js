/**
 * BicBucStriim - behavioural harness for js/installcheck.js
 *
 * Not a standalone test: it is driven by tests/Utilities/InstallCheckPopupTest.php,
 * which renders templates/installcheck.twig, extracts the popup structure with a
 * real HTML parser and passes it here as JSON. The shared jsdom boot + XHR +
 * assertion plumbing lives in harness_lib.js.
 *
 * Usage: yarn node installcheck_popup_harness.js <fixture.json> <project-root>
 * Exits 0 when every assertion passes, 1 otherwise.
 */
(function () {
  'use strict';

  var lib = require('./harness_lib.js');
  var fixture = lib.readFixture(process.argv);
  var root = process.argv[3];
  var c = lib.createChecker(fixture.label);

  /**
   * Production markup for the extracted popups/triggers. The two robustness
   * probes (foreign + dangling href) carry data-probe so the assertion loop
   * can skip them; js/installcheck.js itself binds them like any trigger.
   */
  function buildHtml() {
    var parts = ['<!doctype html><html><head><title>installcheck</title></head><body>'];
    if (fixture.hasBackdrop) {
      parts.push('<div class="bbs-drawer-backdrop"></div>');
    }
    fixture.triggers.forEach(function (t) {
      parts.push('<a data-rel="popup" href="' + t.href + '">open</a>');
    });
    parts.push('<a data-rel="popup" data-probe="external" href="https://example.com/page">external</a>');
    parts.push('<a data-rel="popup" data-probe="dangling" href="#doesNotExist">dangling</a>');
    fixture.popups.forEach(function (p) {
      parts.push('<div class="bbs-popup" id="' + p.id + '">');
      for (var n = 0; n < p.closeButtons; n++) {
        parts.push('<a data-rel="back" href="#">close</a>');
      }
      parts.push('</div>');
    });
    parts.push('</body></html>');
    return parts.join('');
  }

  (async function () {
    var env = await lib.boot(root, 'installcheck.js', buildHtml());
    var doc = env.document;

    var backdrop = doc.querySelector('.bbs-drawer-backdrop');
    var popups = doc.querySelectorAll('.bbs-popup');
    var realTriggers = doc.querySelectorAll('a[data-rel="popup"]:not([data-probe])');
    var probeExternal = doc.querySelector('a[data-probe="external"]');
    var probeDangling = doc.querySelector('a[data-probe="dangling"]');

    function anyOpen() {
      return doc.querySelector('.bbs-popup.open') !== null;
    }

    /** Same target guard js/installcheck.js applies before opening. */
    function popupFor(a) {
      var href = a.getAttribute('href') || '';
      if (href.indexOf('#') !== 0) return null;
      var el = doc.getElementById(href.substring(1));
      return el && el.className.indexOf('bbs-popup') !== -1 ? el : null;
    }

    function closeEverything() {
      var closeBtns = doc.querySelectorAll('.bbs-popup a[data-rel="back"]');
      for (var k = 0; k < closeBtns.length; k++) {
        env.fire(closeBtns[k], 'click');
      }
      if (anyOpen()) env.fire(backdrop, 'click');
    }

    // ------------------------------------------------------------- assertions
    c.check('script loads without errors', env.error === null);
    c.check('backdrop element is available', !!backdrop);
    c.check('no popup is open initially', !anyOpen());
    c.check('backdrop is not dimmed initially', !backdrop.classList.contains('open'));

    // Every split button in the template must open its own popup.
    for (var i = 0; i < realTriggers.length; i++) {
      (function (t, idx) {
        var target = popupFor(t);
        c.check('trigger ' + idx + ' (' + t.getAttribute('href') + ') targets a real popup', !!target);
        if (!target) return;
        env.fire(t, 'click');
        c.check('trigger ' + idx + ' opens #' + target.id, target.classList.contains('open'));
        var othersClosed = true;
        for (var p = 0; p < popups.length; p++) {
          if (popups[p] !== target && popups[p].classList.contains('open')) othersClosed = false;
        }
        c.check('trigger ' + idx + ' leaves the others closed', othersClosed);
        c.check('trigger ' + idx + ' dims the backdrop', backdrop.classList.contains('open'));
      })(realTriggers[i], i);
    }

    closeEverything();
    c.check('close button closes the popup', !anyOpen());
    c.check('close button clears the backdrop', !backdrop.classList.contains('open'));

    if (realTriggers.length && popupFor(realTriggers[0])) {
      env.fire(realTriggers[0], 'click');
      env.fire(backdrop, 'click');
      c.check('backdrop click closes the popup', !anyOpen());

      env.fire(realTriggers[0], 'click');
      env.keydown('Escape');
      c.check('Escape closes the popup', !anyOpen());
    }

    // Robustness: hrefs the script cannot resolve must be ignored, not throw.
    var threw = false;
    try {
      env.fire(probeExternal, 'click');
    } catch (e) {
      threw = true;
    }
    c.check('absolute href is ignored without throwing', !threw && !anyOpen());

    threw = false;
    try {
      env.fire(probeDangling, 'click');
    } catch (e) {
      threw = true;
    }
    c.check('dangling href is ignored without throwing', !threw && !anyOpen());

    c.finish('PASS ' + fixture.label +
      ' (' + fixture.popups.length + ' popups, ' + fixture.triggers.length + ' triggers)');
  })().catch(function (e) {
    console.error(e && e.stack ? e.stack : e);
    process.exit(1);
  });
})();
