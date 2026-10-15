Feature: Author notes
  # Client flow: script.js handles #author-note-edit submission and #delete-notes.
  # tests/js/author_notes_harness.js (driven by AuthorNotesScriptTest::testNoteEditorScriptBehaviour)
  # asserts POST/DELETE requests and inline preview updates.

  An author can have an editable note.

  Scenario: Add and delete an author note
    When I request the notes for author "10"
    And I add the author note "Hello world!"
    Then the author note response contains the rendered note
    When I delete the author note
    Then the author has no note
    # Actions: MetadataActions::getAuthorNote GET /metadata/authors/{id}/notes/ (meta-author-note); editAuthorNote POST to the same path (meta-author-note-post); delAuthorNote DELETE to the same path (meta-author-note-delete).
    # PHPUnit API and model coverage: tests/Actions/MetadataActionsTest.php::testAuthorNote.
