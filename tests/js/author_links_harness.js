/**
 * BicBucStriim - behavioural harness for the author links handlers in js/script.js
 *
 * Not a standalone test: it is driven by tests/Utilities/AuthorLinksScriptTest.php,
 * which renders the author details page through the real app, extracts the links
 * editor structure with a real HTML parser and passes it here as JSON. The shared
 * jsdom boot + XHR + assertion plumbing lives in harness_lib.js.
 *
 * Usage: yarn node author_links_harness.js <fixture.json> <project-root>
 * Exits 0 when every assertion passes, 1 otherwise.
 */
(function () {
  'use strict';

  var lib = require('./harness_lib.js');
  var fixture = lib.readFixture(process.argv);
  var root = process.argv[3];
  var c = lib.createChecker(fixture.label);

  /** Production markup of the links editor, from the PHP-extracted structure. */
  function pageHtml() {
    var ex = fixture.existingLink;
    return [
      '<!doctype html><html><head><title>author details</title></head><body>',
      // #delete-image lives in the thumbnail panel on the real page; the link
      // delete flow falls back to its data-proot/data-author when a delete
      // button carries only data-link (the dynamically added rows)
      '<section id="author-mdthumb-panel">',
      '<div id="flash"></div>',
      '<a href="#" id="delete-image" data-proot="' + fixture.deleteProot + '"',
      ' data-author="' + fixture.deleteAuthor + '">Delete</a>',
      '</section>',
      '<section id="author-mdlinks-panel">',
      '<div id="flash"></div>',
      '<form id="author-link-new" action="' + fixture.formAction + '">',
      '<input type="text" name="link-description" id="link-description" />',
      '<input type="url" name="link-url" id="link-url" />',
      '</form>',
      '<ul id="author-links-edit">',
      '<li class="bbs-list-item link-' + ex.id + '">',
      '<a href="' + ex.url + '">' + ex.label + '</a> ',
      '<a href="#" data-proot="' + fixture.deleteProot + '" data-author="' + fixture.deleteAuthor + '"',
      ' data-link="' + ex.id + '" class="bbs-btn bbs-btn-danger bbs-btn-mini author-link-delete">Delete</a>',
      '</li>',
      '</ul>',
      '</section>',
      '<ul id="author-links-list">',
      '<li class="author-link link-0"><a class="bbs-btn bbs-btn-mini" rel="external" target="_blank" href="http://localhost/calibre/">Calibre</a></li>',
      '<li class="author-link link-' + ex.id + '">',
      '<a class="bbs-btn bbs-btn-mini" target="_blank" rel="external" href="' + ex.url + '">' + ex.label + '</a>',
      '</li>',
      '</ul>',
      '</body></html>'
    ].join('');
  }

  function flashOk(doc, text) {
    var flashes = doc.querySelectorAll('#flash');
    if (flashes.length !== 2) return false;
    for (var i = 0; i < flashes.length; i++) {
      var html = flashes[i].innerHTML;
      if (html.indexOf('success') === -1 || html.indexOf(text) === -1) return false;
    }
    return true;
  }

  function flashError(doc, text) {
    var flashes = doc.querySelectorAll('#flash');
    if (flashes.length !== 2) return false;
    for (var i = 0; i < flashes.length; i++) {
      var html = flashes[i].innerHTML;
      if (html.indexOf('error') === -1 || html.indexOf(text) === -1) return false;
    }
    return true;
  }

  (async function () {
    var env = await lib.boot(root, 'script.js', pageHtml());
    var doc = env.document;
    var ex = fixture.existingLink;
    var formAction = fixture.formAction;
    var linkUrl = function (id) {
      return (fixture.deleteProot || '') + '/metadata/authors/' + fixture.deleteAuthor + '/links/' + id + '/';
    };

    c.check('script loads with the links editor elements present', env.error === null);

    // --------------------------------------------------------- 1. add a link
    var descInput = doc.getElementById('link-description');
    var urlInput = doc.getElementById('link-url');
    descInput.value = fixture.typedLabel;
    urlInput.value = fixture.typedUrl;
    var prevented = env.fire(doc.getElementById('author-link-new'), 'submit');
    c.check('add prevents the native form submit', prevented);

    var req = env.xhr.last();
    c.check('add issues an XHR', !!req);
    c.check('add uses POST', req && req.type === 'POST');
    c.check('add posts to the form action from the template', req && req.url === formAction);
    c.check('add sends a urlencoded body',
      req && req.headers['Content-Type'].indexOf('application/x-www-form-urlencoded') === 0);
    c.check('add maps the inputs to the label/url params MetadataActions expects',
      req && req.body === 'label=' + encodeURIComponent(fixture.typedLabel) +
        '&url=' + encodeURIComponent(fixture.typedUrl));

    var newId = '7';
    req.respond(200, JSON.stringify({
      link: { id: newId, label: fixture.typedLabel, url: fixture.typedUrl },
      msg: 'Changes applied'
    }));
    var newListItem = doc.querySelector('#author-links-list li.link-' + newId);
    c.check('new link is spliced into the public list',
      !!newListItem && newListItem.querySelector('a').getAttribute('href') === fixture.typedUrl &&
        newListItem.querySelector('a').textContent === fixture.typedLabel);
    var newEditItem = doc.querySelector('#author-links-edit li.link-' + newId);
    var newDeleteBtn = newEditItem && newEditItem.querySelector('.author-link-delete');
    c.check('new link is spliced into the edit list with a delete button bound to its id',
      !!newDeleteBtn && newDeleteBtn.getAttribute('data-link') === newId);
    c.check('inputs are cleared after a successful add',
      descInput.value === '' && urlInput.value === '');
    c.check('flash shows the server message in both panels', flashOk(doc, 'Changes applied'));

    // ------------------------------------------------------ 2. a failed add
    descInput.value = fixture.typedLabel;
    urlInput.value = fixture.typedUrl;
    env.fire(doc.getElementById('author-link-new'), 'submit');
    req = env.xhr.last();
    req.respond(500, 'Add error');
    c.check('failed add flashes the server error in both panels', flashError(doc, 'Add error'));
    c.check('failed add keeps the typed values', descInput.value === fixture.typedLabel &&
      urlInput.value === fixture.typedUrl);

    // -------------------------------------------- 3. delete an existing link
    var staticDelete = doc.querySelector('#author-links-edit li.link-' + ex.id + ' .author-link-delete');
    c.check('the template-declared delete button is bound at load', !!staticDelete);
    prevented = env.fire(staticDelete, 'click');
    c.check('delete prevents the default link navigation', prevented);
    req = env.xhr.last();
    c.check('delete uses DELETE', req && req.type === 'DELETE');
    c.check('delete builds the metadata url from the button data attributes',
      req && req.url === linkUrl(ex.id));
    req.respond(200, JSON.stringify({ msg: 'Changes applied' }));
    c.check('delete removes the link from both lists',
      doc.querySelectorAll('.link-' + ex.id).length === 0);
    c.check('delete leaves the other links alone',
      doc.querySelectorAll('#author-links-list .link-0').length === 1);
    c.check('delete flashes the server message in both panels', flashOk(doc, 'Changes applied'));

    // ----------------- 4. delete the dynamically added link (rebind check)
    prevented = env.fire(newDeleteBtn, 'click');
    c.check('the dynamically added delete button was rebound', prevented);
    req = env.xhr.last();
    c.check('rebound delete falls back to the thumbnail panel data attributes for root/author',
      req && req.url === linkUrl(newId));
    req.respond(200, JSON.stringify({ msg: 'Changes applied' }));
    c.check('rebound delete removes the link from both lists',
      doc.querySelectorAll('.link-' + newId).length === 0);
    c.check('rebound delete flashes the server message in both panels', flashOk(doc, 'Changes applied'));

    // --------------------------------- 5. pages without the link elements exist
    var bareEnv = await lib.boot(root, 'script.js',
      '<!doctype html><html><head><title>bare</title></head><body></body></html>');
    c.check('script runs without errors on pages lacking the link elements',
      bareEnv.error === null);

    c.finish('all author links script assertions passed');
  })().catch(function (e) {
    console.error(e && e.stack ? e.stack : e);
    process.exit(1);
  });
})();
