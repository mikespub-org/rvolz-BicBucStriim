/**
 * BicBucStriim - behavioural harness for the author notes handlers in js/script.js
 *
 * Not a standalone test: it is driven by tests/Utilities/AuthorNotesScriptTest.php,
 * which renders the author notes page through the real app, extracts the form and
 * delete-link structure with a real HTML parser and passes it here as JSON. The
 * shared jsdom boot + XHR + assertion plumbing lives in harness_lib.js.
 *
 * Usage: yarn node author_notes_harness.js <fixture.json> <project-root>
 * Exits 0 when every assertion passes, 1 otherwise.
 */
(function () {
  'use strict';

  var lib = require('./harness_lib.js');
  var fixture = lib.readFixture(process.argv);
  var root = process.argv[3];
  var c = lib.createChecker(fixture.label);

  var initialPreview = '<p>Previous rendered note</p>';
  var savedHtml = '<h2>Hello world!</h2>\n';

  /** Production markup of the notes page, from the PHP-extracted structure. */
  function pageHtml() {
    return [
      '<!doctype html><html><head><title>notes</title></head><body>',
      '<div id="flash"></div>',
      '<section id="author-mdnotes-panel">',
      '<form id="author-note-edit" action="' + fixture.formAction + '">',
      '<textarea id="note-edit" name="note-edit"></textarea>',
      '</form>',
      '<a href="#" id="delete-notes" data-proot="' + fixture.deleteProot + '"',
      ' data-author="' + fixture.deleteAuthor + '">Delete</a>',
      '</section>',
      '<section id="author-notes">' + initialPreview + '</section>',
      '</body></html>'
    ].join('');
  }

  (async function () {
    var env = await lib.boot(root, 'script.js', pageHtml());
    var doc = env.document;
    var textInput = doc.getElementById('note-edit');
    var notesEl = doc.getElementById('author-notes');
    var flashEl = doc.getElementById('flash');

    c.check('script loads with the note elements present', env.error === null);

    // ------------------------------------------------------- 1. save a note
    textInput.value = fixture.typedNote;
    var prevented = env.fire(doc.getElementById('author-note-edit'), 'submit');
    c.check('save prevents the native GET form submit', prevented);

    var req = env.xhr.last();
    c.check('save issues an XHR', !!req);
    c.check('save uses POST', req && req.type === 'POST');
    c.check('save posts to the form action from the template', req && req.url === fixture.formAction);
    c.check('save sends a urlencoded body',
      req && req.headers['Content-Type'].indexOf('application/x-www-form-urlencoded') === 0);
    c.check('save sends the ntext and mime params expected by MetadataActions',
      req && req.body === 'ntext=' + encodeURIComponent(fixture.typedNote) +
        '&mime=' + encodeURIComponent('text/markdown'));

    req.respond(200, JSON.stringify({
      note: { id: 1, mime: 'text/markdown', ntext: fixture.typedNote, html: savedHtml },
      msg: 'Changes applied'
    }));
    c.check('preview is updated inline with the server-rendered html',
      notesEl.innerHTML === savedHtml);
    c.check('flash shows the server message as success',
      flashEl.innerHTML.indexOf('success') !== -1 &&
        flashEl.innerHTML.indexOf('Changes applied') !== -1);

    // ------------------------------------------------------- 2. a failed save
    env.fire(doc.getElementById('author-note-edit'), 'submit');
    req = env.xhr.last();
    req.respond(500, 'Modify error');
    c.check('failed save flashes the server error text',
      flashEl.innerHTML.indexOf('error') !== -1 &&
        flashEl.innerHTML.indexOf('Modify error') !== -1);
    c.check('failed save leaves the preview alone', notesEl.innerHTML === savedHtml);

    // ------------------------------------------ 3. success without html field
    env.fire(doc.getElementById('author-note-edit'), 'submit');
    req = env.xhr.last();
    var threw = false;
    try {
      req.respond(200, JSON.stringify({ note: { id: 1 }, msg: 'Changes applied' }));
    } catch (e) {
      threw = true;
    }
    c.check('response without html does not throw', !threw);
    c.check('response without html leaves the preview alone', notesEl.innerHTML === savedHtml);

    // -------------------------------------------------------- 4. delete a note
    var expectedDeleteUrl =
      (fixture.deleteProot || '') + '/metadata/authors/' + fixture.deleteAuthor + '/notes/';
    prevented = env.fire(doc.getElementById('delete-notes'), 'click');
    c.check('delete prevents the default link navigation', prevented);

    req = env.xhr.last();
    c.check('delete uses DELETE', req && req.type === 'DELETE');
    c.check('delete builds the metadata url from data-proot and data-author',
      req && req.url === expectedDeleteUrl);

    textInput.value = fixture.typedNote;
    req.respond(200, JSON.stringify({ msg: 'Changes applied' }));
    c.check('delete clears the textarea', textInput.value === '');
    c.check('delete clears the rendered preview', notesEl.innerHTML === '');
    c.check('delete flashes the server message as success',
      flashEl.innerHTML.indexOf('success') !== -1 &&
        flashEl.innerHTML.indexOf('Changes applied') !== -1);

    // --------------------------------- 5. pages without the note elements exist
    var bareEnv = await lib.boot(root, 'script.js',
      '<!doctype html><html><head><title>bare</title></head><body></body></html>');
    c.check('script runs without errors on pages lacking the note elements',
      bareEnv.error === null);

    c.finish('all author notes script assertions passed');
  })().catch(function (e) {
    console.error(e && e.stack ? e.stack : e);
    process.exit(1);
  });
})();
