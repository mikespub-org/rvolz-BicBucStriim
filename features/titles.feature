Feature: The titles list displays all books in the library.

  The titles list is ordered alphabetically by title and paginated according
  to the configuration settings.

  Scenario: The Books menu shows the first page
    When I click on menu item "Books"
    Then the app switches to the "Books" view
    And the list contains 2 items
    And the menu item "Books" is active
    And there are no "previous" buttons
    And there are "next" buttons
    # Action: MainActions::titlesSlice, GET /titles/ (main-titles-v2); legacy GET /titleslist/{page}/ (main-titles-v1).
    # PHPUnit route/render smoke coverage: tests/Actions/MainActionsTest.php::testAppGetRequest (/titles/).

  Scenario: Clicking a book opens its details page
    When I click on menu item "Books"
    And the app switches to the "Books" view
    And I click on list item "Lob der Faulheit"
    Then the "Books" details page 1 for "Lob der Faulheit" appears
    # Action: MainActions::title, GET /titles/{id}/ (main-title).
    # PHPUnit route/render smoke coverage: tests/Actions/MainActionsTest.php::testAppGetRequest (/titles/{id}/).

  Scenario: The next button shows the next two books
    When I click on menu item "Books"
    And the app switches to the "Books" view
    And I click on the "next" button
    Then "Books" page 1 with content "Neues Leben" appears
    And the list contains 2 items
    And there are "previous" buttons
    And there are "next" buttons
    # Action: MainActions::titlesSlice, GET /titles/ (main-titles-v2); legacy GET /titleslist/{page}/ (main-titles-v1).
    # Underlying pagination: tests/Calibre/CalibreTest.php::testTitlesSlice.
    # No MainActions test asserts page navigation or rendered controls.

  Scenario: The previous button returns to the first page
    When I click on menu item "Books"
    And the app switches to the "Books" view
    And I go to the "Books" page 1
    And I click on the "previous" button
    Then "Books" page 0 with content "Lob der Faulheit" appears
    And the list contains 2 items
    # Action: MainActions::titlesSlice, GET /titles/ (main-titles-v2); legacy GET /titleslist/{page}/ (main-titles-v1).
    # Underlying pagination: tests/Calibre/CalibreTest.php::testTitlesSlice.
    # No MainActions test asserts page navigation or rendered controls.

  Scenario: The last page has only a previous button
    When I click on menu item "Books"
    And the app switches to the "Books" view
    And I go to the "Books" page 3
    Then "Books" page 3 with content "Zwei Wege sinds" appears
    And the list contains 1 item
    And there are "previous" buttons
    And there are no "next" buttons
    # Action: MainActions::titlesSlice, GET /titles/ (main-titles-v2); legacy GET /titleslist/{page}/ (main-titles-v1).
    # Underlying pagination: tests/Calibre/CalibreTest.php::testTitlesSlice.
    # No MainActions test asserts the rendered last-page controls.

  Scenario: Switch between title and date sorting
    When I click on menu item "Books"
    And the app switches to the "Books" view
    And I select the date sorting option
    Then the titles are ordered by the configured date field, newest first
    When I select the title sorting option
    Then the titles are ordered alphabetically by title
    # Action: MainActions::titlesSlice, GET /titles/ (main-titles-v2); legacy GET /titleslist/{page}/ (main-titles-v1), with sort query.
    # MainActions::titlesSlice supports sort=byReverseDate; title_time_sort
    # selects timestamp, publication date, or last-modified ordering.
    # Underlying date ordering: tests/Calibre/CalibreTest.php::testTimestampOrderedTitlesSlice,
    # testPubdateOrderedTitlesSlice and testLastmodifiedOrderedTitlesSlice.
    # No MainActions test asserts the sort-toggle interaction.

  Scenario: Searching from the books list opens global search
    When I click on menu item "Books"
    And the app switches to the "Books" view
    And I enter "sto" into the "titles" search field
    Then the search result page appears
    And the page contains "Books: 1"
    And the page contains "Authors: 1"
    And the page contains "Tags: 0"
    And the page contains "Series: 0"
    # Action: MainActions::globalSearch, GET/POST /search/ (main-search, main-search-post).
    # Underlying category search: tests/Calibre/CalibreTest.php::testTitlesSliceSearch,
    # testAuthorsSliceSearch, testTagsSliceSearch and testSeriesSliceSearch.
    # No MainActions test asserts the rendered global-search result page.
