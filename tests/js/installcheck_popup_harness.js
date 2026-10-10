/**
 * BicBucStriim - behavioural harness for js/installcheck.js
 *
 * Not a standalone test: it is driven by tests/Utilities/InstallCheckPopupTest.php,
 * which renders templates/installcheck.twig, extracts the popup structure with a
 * real HTML parser and passes it here as JSON. This keeps the assertions small
 * while still exercising the production script against production markup.
 *
 * Usage: node installcheck_popup_harness.js <fixture.json> <project-root>
 * Exits 0 when every assertion passes, 1 otherwise.
 */
(function () {
  'use strict';

  var fs = require('fs');
  var vm = require('vm');
  var path = require('path');

  var fixture = JSON.parse(fs.readFileSync(process.argv[2], 'utf8'));
  var root = process.argv[3];

  // ---------------------------------------------------------------- DOM stub
  function makeEl(tag) {
    var el = {
      tagName: tag,
      className: '',
      attrs: {},
      children: [],
      parentNode: null,
      classes: {},
      listeners: {},
    };
    el.classList = {
      add: function (c) { el.classes[c] = true; },
      remove: function (c) { delete el.classes[c]; },
      contains: function (c) { return !!el.classes[c]; },
    };
    el.addEventListener = function (type, fn) {
      (el.listeners[type] = el.listeners[type] || []).push(fn);
    };
    el.fire = function (type, ev) {
      // Browsers bind `this` to the target element for listeners.
      var e = ev || { preventDefault: function () {} };
      (el.listeners[type] || []).forEach(function (fn) { fn.call(el, e); });
    };
    el.appendChild = function (c) { el.children.push(c); return c; };
    el.setAttribute = function (k, v) { el.attrs[k] = String(v); };
    el.getAttribute = function (k) {
      return Object.prototype.hasOwnProperty.call(el.attrs, k) ? el.attrs[k] : null;
    };
    el.querySelector = function () { return null; };
    el.querySelectorAll = function () { return []; };
    return el;
  }

  var popups = fixture.popups.map(function (p) {
    var el = makeEl('div');
    el.id = p.id;
    el.className = 'bbs-popup';
    return el;
  });

  var triggers = fixture.triggers.map(function (t) {
    var el = makeEl('a');
    el.setAttribute('href', t.href);
    el.setAttribute('data-rel', 'popup');
    return el;
  });

  var closeButtons = [];
  fixture.popups.forEach(function (p, i) {
    for (var n = 0; n < p.closeButtons; n++) {
      var btn = makeEl('a');
      btn.setAttribute('data-rel', 'back');
      btn.parentNode = popups[i];
      closeButtons.push(btn);
    }
  });

  var existingBackdrop = null;
  if (fixture.hasBackdrop) {
    existingBackdrop = makeEl('div');
    existingBackdrop.className = 'bbs-drawer-backdrop';
  }

  var appended = [];
  var docListeners = {};
  var documentStub = {
    readyState: 'complete',
    addEventListener: function (t, fn) {
      (docListeners[t] = docListeners[t] || []).push(fn);
    },
    querySelector: function (sel) {
      if (sel === '.bbs-drawer-backdrop') return existingBackdrop;
      if (sel === '.bbs-popup.open') {
        for (var i = 0; i < popups.length; i++) {
          if (popups[i].classList.contains('open')) return popups[i];
        }
        return null;
      }
      if (sel.indexOf('.bbs-panel.open') === 0) return null;
      return null;
    },
    querySelectorAll: function (sel) {
      if (sel === 'a[data-rel="popup"]') return triggers;
      if (sel === '.bbs-popup a[data-rel="back"]') return closeButtons;
      if (sel === '.bbs-popup.open') {
        return popups.filter(function (p) { return p.classList.contains('open'); });
      }
      return [];
    },
    getElementById: function (id) {
      for (var i = 0; i < popups.length; i++) if (popups[i].id === id) return popups[i];
      return null;
    },
    createElement: function (tag) { return makeEl(tag); },
    body: { appendChild: function (c) { appended.push(c); return c; } },
  };

  var sandbox = { document: documentStub, console: console };
  sandbox.window = sandbox;
  vm.createContext(sandbox);
  vm.runInContext(fs.readFileSync(path.join(root, 'js', 'installcheck.js'), 'utf8'), sandbox);

  var backdrop = existingBackdrop;
  if (!backdrop) {
    for (var b = 0; b < appended.length; b++) {
      if (appended[b].className === 'bbs-drawer-backdrop') backdrop = appended[b];
    }
  }

  // ------------------------------------------------------------- assertions
  var failures = 0;
  function check(name, cond) {
    console.log((cond ? '  ok   ' : '  FAIL ') + name);
    if (!cond) failures++;
  }
  function targetIndex(el) {
    var href = el.getAttribute('href') || '';
    if (href.indexOf('#') !== 0) return -1;
    var id = href.substring(1);
    for (var i = 0; i < popups.length; i++) if (popups[i].id === id) return i;
    return -1;
  }
  function anyOpen() {
    return popups.some(function (p) { return p.classList.contains('open'); });
  }
  function closeEverything() {
    closeButtons.forEach(function (b) { b.fire('click'); });
    if (anyOpen()) backdrop.fire('click');
  }

  check('backdrop element is available', !!backdrop);
  check('no popup is open initially', !anyOpen());
  check('backdrop is not dimmed initially', !backdrop.classList.contains('open'));

  // Every split button in the template must open its own popup.
  triggers.forEach(function (t, i) {
    var idx = targetIndex(t);
    check('trigger ' + i + ' (' + t.getAttribute('href') + ') targets a real popup', idx >= 0);
    if (idx < 0) return;
    t.fire('click');
    check('trigger ' + i + ' opens #' + popups[idx].id, popups[idx].classList.contains('open'));
    check('trigger ' + i + ' leaves the others closed',
      popups.every(function (p, j) { return j === idx || !p.classList.contains('open'); }));
    check('trigger ' + i + ' dims the backdrop', backdrop.classList.contains('open'));
  });

  closeEverything();
  check('close button closes the popup', !anyOpen());
  check('close button clears the backdrop', !backdrop.classList.contains('open'));

  if (triggers.length && targetIndex(triggers[0]) >= 0) {
    triggers[0].fire('click');
    backdrop.fire('click');
    check('backdrop click closes the popup', !anyOpen());

    triggers[0].fire('click');
    (docListeners.keydown || []).forEach(function (fn) {
      fn.call(documentStub, { keyCode: 27, key: 'Escape', preventDefault: function () {} });
    });
    check('Escape closes the popup', !anyOpen());
  }

  // Robustness: hrefs the script cannot resolve must be ignored, not throw.
  var foreign = makeEl('a');
  foreign.setAttribute('href', 'https://example.com/page');
  foreign.setAttribute('data-rel', 'popup');
  var threw = false;
  try { foreign.fire('click'); } catch (e) { threw = true; }
  check('absolute href is ignored without throwing', !threw && !anyOpen());

  var dangling = makeEl('a');
  dangling.setAttribute('href', '#doesNotExist');
  dangling.setAttribute('data-rel', 'popup');
  threw = false;
  try { dangling.fire('click'); } catch (e) { threw = true; }
  check('dangling href is ignored without throwing', !threw && !anyOpen());

  console.log(failures === 0
    ? 'PASS ' + fixture.label + ' (' + popups.length + ' popups, ' + triggers.length + ' triggers)'
    : 'FAIL ' + fixture.label + ' - ' + failures + ' assertion(s) failed');

  process.exit(failures === 0 ? 0 : 1);
})();
