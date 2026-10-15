/**
 * BicBucStriim - behavioural harness for the author notes handlers in js/script.js
 *
 * Not a standalone test: it is driven by tests/Utilities/AuthorNotesScriptTest.php,
 * which renders the author notes page through the real app, extracts the form and
 * delete-link structure with a real HTML parser and passes it here as JSON. This
 * keeps the assertions small while still exercising the production script against
 * production markup.
 *
 * Usage: node author_notes_harness.js <fixture.json> <project-root>
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
      classes: {},
      listeners: {},
      innerHTML: '',
      value: ''
    };
    el.classList = {
      add: function (c) { el.classes[c] = true; },
      remove: function (c) { delete el.classes[c]; },
      contains: function (c) { return !!el.classes[c]; }
    };
    el.addEventListener = function (type, fn) {
      (el.listeners[type] = el.listeners[type] || []).push(fn);
    };
    el.fire = function (type, ev) {
      // Browsers bind `this` to the target element for listeners.
      var e = ev || { preventDefault: function () {} };
      (el.listeners[type] || []).forEach(function (fn) { fn.call(el, e); });
    };
    el.appendChild = function (c) { return c; };
    el.removeChild = function () { return null; };
    el.querySelector = function () { return null; };
    el.querySelectorAll = function () { return []; };
    el.setAttribute = function (k, v) { el.attrs[k] = String(v); };
    el.getAttribute = function (k) {
      return Object.prototype.hasOwnProperty.call(el.attrs, k) ? el.attrs[k] : null;
    };
    el.focus = function () {};
    return el;
  }

  var initialPreview = '<p>Previous rendered note</p>';

  var els = {
    flash: makeEl('div'),
    'author-note-edit': makeEl('form'),
    'note-edit': makeEl('textarea'),
    'author-notes': makeEl('section'),
    'delete-notes': makeEl('a')
  };
  els['author-note-edit'].setAttribute('action', fixture.formAction);
  els['note-edit'].value = fixture.typedNote;
  els['author-notes'].innerHTML = initialPreview;
  els['delete-notes'].setAttribute('data-proot', fixture.deleteProot);
  els['delete-notes'].setAttribute('data-author', fixture.deleteAuthor);

  function makeDocument(getElementById) {
    return {
      readyState: 'complete',
      cookie: '',
      body: makeEl('body'),
      createElement: function (tag) { return makeEl(tag); },
      getElementById: getElementById,
      querySelector: function () { return null; },
      querySelectorAll: function (sel) {
        // support the only selector js/script.js needs in bulk: #flash
        if (sel === '#flash') return Object.prototype.hasOwnProperty.call(els, 'flash') ? [els.flash] : [];
        return [];
      },
      addEventListener: function () {}
    };
  }

  var documentStub = makeDocument(function (id) {
    return Object.prototype.hasOwnProperty.call(els, id) ? els[id] : null;
  });

  // ---------------------------------------------------------------- XHR stub
  var xhrLog = [];
  function FakeXHR() {
    this.readyState = 0;
    this.status = 0;
    this.responseText = '';
    this.headers = {};
    xhrLog.push(this);
  }
  FakeXHR.prototype.open = function (type, url) { this.type = type; this.url = url; };
  FakeXHR.prototype.setRequestHeader = function (k, v) { this.headers[k] = v; };
  FakeXHR.prototype.send = function (body) { this.body = body; };
  FakeXHR.prototype.respond = function (status, body) {
    this.status = status;
    this.responseText = body;
    this.readyState = 4;
    this.onreadystatechange();
  };

  function runScript(documentForSandbox) {
    var sandbox = {
      document: documentForSandbox,
      window: {},
      XMLHttpRequest: FakeXHR,
      FormData: function FormData() {},
      setTimeout: function (fn) { fn(); },
      encodeURIComponent: encodeURIComponent,
      decodeURIComponent: decodeURIComponent,
      console: console
    };
    vm.createContext(sandbox);
    var threw = null;
    try {
      vm.runInContext(fs.readFileSync(path.join(root, 'js', 'script.js'), 'utf8'), sandbox);
    } catch (e) {
      threw = e;
    }
    return threw;
  }

  // ------------------------------------------------------------- assertions
  var failures = 0;
  function check(name, cond) {
    console.log((cond ? '  ok   ' : '  FAIL ') + name);
    if (!cond) failures++;
  }
  function lastXhr() {
    return xhrLog[xhrLog.length - 1];
  }

  var loadError = runScript(documentStub);
  check('script loads with the note elements present', !loadError);

  // ---------------------------------------------------------- 1. save a note
  var prevented = false;
  els['author-note-edit'].fire('submit', {
    preventDefault: function () { prevented = true; }
  });
  check('save prevents the native GET form submit', prevented);

  var req = lastXhr();
  check('save issues an XHR', !!req);
  check('save uses POST', req && req.type === 'POST');
  check('save posts to the form action from the template', req && req.url === fixture.formAction);
  check('save sends a urlencoded body',
    req && req.headers['Content-Type'].indexOf('application/x-www-form-urlencoded') === 0);
  check('save sends the ntext and mime params expected by MetadataActions',
    req && req.body === 'ntext=' + encodeURIComponent(fixture.typedNote) +
      '&mime=' + encodeURIComponent('text/markdown'));

  var savedHtml = '<h2>Hello world!</h2>\n';
  req.respond(200, JSON.stringify({
    note: { id: 1, mime: 'text/markdown', ntext: fixture.typedNote, html: savedHtml },
    msg: 'Changes applied'
  }));
  check('preview is updated inline with the server-rendered html',
    els['author-notes'].innerHTML === savedHtml);
  check('flash shows the server message as success',
    els.flash.innerHTML.indexOf('success') !== -1 &&
      els.flash.innerHTML.indexOf('Changes applied') !== -1);

  // ------------------------------------------------------- 2. a failed save
  els['author-note-edit'].fire('submit', { preventDefault: function () {} });
  req = lastXhr();
  req.respond(500, 'Modify error');
  check('failed save flashes the server error text',
    els.flash.innerHTML.indexOf('error') !== -1 &&
      els.flash.innerHTML.indexOf('Modify error') !== -1);
  check('failed save leaves the preview alone', els['author-notes'].innerHTML === savedHtml);

  // ------------------------------------------- 3. success without html field
  els['author-note-edit'].fire('submit', { preventDefault: function () {} });
  req = lastXhr();
  var threw = false;
  try {
    req.respond(200, JSON.stringify({ note: { id: 1 }, msg: 'Changes applied' }));
  } catch (e) {
    threw = true;
  }
  check('response without html does not throw', !threw);
  check('response without html leaves the preview alone', els['author-notes'].innerHTML === savedHtml);

  // -------------------------------------------------------- 4. delete a note
  var expectedDeleteUrl =
    (fixture.deleteProot || '') + '/metadata/authors/' + fixture.deleteAuthor + '/notes/';
  prevented = false;
  els['delete-notes'].fire('click', {
    preventDefault: function () { prevented = true; }
  });
  check('delete prevents the default link navigation', prevented);

  req = lastXhr();
  check('delete uses DELETE', req && req.type === 'DELETE');
  check('delete builds the metadata url from data-proot and data-author',
    req && req.url === expectedDeleteUrl);

  els['note-edit'].value = fixture.typedNote;
  req.respond(200, JSON.stringify({ msg: 'Changes applied' }));
  check('delete clears the textarea', els['note-edit'].value === '');
  check('delete clears the rendered preview', els['author-notes'].innerHTML === '');
  check('delete flashes the server message as success',
    els.flash.innerHTML.indexOf('success') !== -1 &&
      els.flash.innerHTML.indexOf('Changes applied') !== -1);

  // --------------------------------- 5. pages without the note elements exist
  var bareError = runScript(makeDocument(function () { return null; }));
  check('script runs without errors on pages lacking the note elements', !bareError);

  if (failures > 0) {
    console.log(failures + ' author notes script assertion(s) failed');
    process.exit(1);
  }
  console.log('all author notes script assertions passed');
})();
