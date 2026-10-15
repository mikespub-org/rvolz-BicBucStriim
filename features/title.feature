Feature: Title details

  The title details page provides a data summary and downloads.

  Scenario: A simple title page with cover image, author, tag and download
    When I click on menu item "Books"
    And the app switches to the "Books" view
    And I click on list item "Glücksritter"
    Then the "Books" details page 4 for "Die Glücksritter" appears
    # Action: MainActions::title, GET /titles/{id}/ (main-title).
    # Route/render smoke coverage: tests/Actions/MainActionsTest.php::testAppGetRequest (/titles/{id}/).

  Scenario: Books can be downloaded
    When I click on menu item "Books"
    And the app switches to the "Books" view
    And I click on list item "Glücksritter"
    And the "Books" details page 4 for "Die Glücksritter" appears
    Then clicking on "Download" reveals the download options
    And clicking on download format "EPUB" starts the download for file
      "Die Glucksritter - Joseph von Eichendorff.epub" and length 10175
    # Actions: MainActions::title, GET /titles/{id}/ (main-title); MainActions::book, GET /titles/{id}/file/{file} (main-book).
    # Route/file smoke coverage: tests/Actions/MainActionsTest.php::testAppGetRequest
    # for /titles/{id}/file/{file}; its fixture is a different book and does not
    # assert this EPUB's exact length.

  Scenario: Author links lead to author pages
    When I click on menu item "Books"
    And the app switches to the "Books" view
    And I click on list item "Glücksritter"
    And the "Books" details page 4 for "Die Glücksritter" appears
    And I click on author "Joseph von Eichendorff"
    Then the "Authors" details page 5 for "Joseph von Eichendorff" appears
    # Actions: MainActions::title, GET /titles/{id}/ (main-title); MainActions::authorDetailsSlice, GET /authors/{id}/{page}/ (main-author-v1) or /authors/{id}/ (main-author-v2).
    # The details route is covered by tests/Actions/MainActionsTest.php::testAppGetRequest;
    # the author-link navigation itself has no PHPUnit assertion.

  Scenario: Tag links lead to tag pages
    When I click on menu item "Books"
    And the app switches to the "Books" view
    And I click on list item "Glücksritter"
    And the "Books" details page 4 for "Die Glücksritter" appears
    And I click on "Tags" to reveal the tags
    And I click on tag "Fachbücher"
    Then the "Tags" details page 3 for "Fachbücher" appears
    # Actions: MainActions::title, GET /titles/{id}/ (main-title); MainActions::tagDetailsSlice, GET /tags/{id}/{page}/ (main-tag-v1) or /tags/{id}/ (main-tag-v2).
    # The title route is covered by tests/Actions/MainActionsTest.php::testAppGetRequest;
    # the tag-link navigation itself has no PHPUnit assertion.

  Scenario: If a book is not part of a series, no series links are provided
    When I click on menu item "Books"
    And the app switches to the "Books" view
    And I click on list item "Glücksritter"
    And the "Books" details page 4 for "Die Glücksritter" appears
    Then there are no "Series" links
    # Action: MainActions::title, GET /titles/{id}/ (main-title); series-link visibility is rendered by title_detail.twig.
    # No PHPUnit test currently asserts the conditional series block rendering.

  Scenario: If a book is part of a series, series links are provided
    When I click on menu item "Books"
    And the app switches to the "Books" view
    And I click on list item "Lob der Faulheit"
    And the "Books" details page 1 for "Lob der Faulheit" appears
    Then there are "Series" links
    And I click on "Series" to reveal the series
    And the series link contains the text "Serie Lessing (1.0)"
    And I click on series "Serie Lessing"
    Then the "Series" details page 4 for "Serie Lessing" appears
    # Actions: MainActions::title, GET /titles/{id}/ (main-title); MainActions::seriesDetailsSlice, GET /series/{id}/{page}/ (main-serie-v1) or /series/{id}/ (main-serie-v2).
    # The series details route is covered by tests/Actions/MainActionsTest.php::testAppGetRequest;
    # the conditional series link rendering has no PHPUnit assertion.

  Scenario: If a book has no custom column info, no info is displayed
    When I click on menu item "Books"
    And the app switches to the "Books" view
    And I click on list item "Glücksritter"
    And the "Books" details page 4 for "Die Glücksritter" appears
    Then there is no custom column info
    # Action: MainActions::title, GET /titles/{id}/ (main-title); custom columns are rendered by title_detail.twig.
    # No PHPUnit test currently asserts the conditional custom-column rendering.

  Scenario: If a book has custom column info, that is displayed
    When I click on menu item "Books"
    And the app switches to the "Books" view
    And I click on list item "Lob der Faulheit"
    And the "Books" details page 1 for "Lob der Faulheit" appears
    Then there is custom column info
    And I click on "Custom Calibre Data" to reveal the custom column info "Col6"
    # Action: MainActions::title, GET /titles/{id}/ (main-title); custom columns are rendered by title_detail.twig.
    # No PHPUnit test currently asserts custom-column rendering.
