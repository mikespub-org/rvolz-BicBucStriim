Feature: Author details pages list books by an author

  The details pages for authors list all books by an author in the library.
  If there is no custom image or link, the page shows the author's name and
  the default thumbnail in the list view.

  Scenario: Page lists all books
    When I click on menu item "Authors"
    And the app switches to the "Authors" view
    And I click on list item "Grimmelshausen"
    Then the "Authors" details page 6 for "Grimmelshausen" appears
    And just name "Hans Jakob Christoffel von Grimmelshausen" is mentioned
    And there is no author image
    And there are no author links
    And there are two books
    # Action: MainActions::authorDetailsSlice, GET /authors/{id}/ (main-author-v2); legacy GET /authors/{id}/{page}/ (main-author-v1).
    # PHPUnit route/render smoke coverage: tests/Actions/MainActionsTest.php::testAppGetRequest (/authors/{id}/).
    # No PHPUnit test currently asserts this rendered author content.

  Scenario: Author has the default thumbnail
    When I click on menu item "Authors"
    And the app switches to the "Authors" view
    Then the entry for author "Grimmelshausen" shows the default thumbnail
    # Actions: MainActions::authorsSlice, GET /authors/ (main-authors-v2); MetadataActions::getAuthorThumbnail, GET /metadata/authors/{id}/thumbnail/ (meta-author-thumb).
    # Thumbnail API/model coverage: tests/Actions/MetadataActionsTest.php::testGetAuthorThumbnail
    # and tests/AppData/AuthorsTest.php::testGetAuthorThumbnail.
