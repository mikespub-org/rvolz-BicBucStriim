Feature: The series list displays all book series in the library.

  The series list is ordered by name and paginated according to the
  configuration settings.

  Scenario: The Series menu shows the first page
    When I click on menu item "Series"
    Then the app switches to the "Series" view
    And the list contains 2 items
    And the menu item "Series" is active
    And there are no "previous" buttons
    And there are "next" buttons
    # Action: MainActions::seriesSlice, GET /series/ (main-series-v2); legacy GET /serieslist/{page}/ (main-series-v1).
    # PHPUnit route/render smoke coverage: tests/Actions/MainActionsTest.php::testAppGetRequest (/series/)

  Scenario: Clicking a series opens its details page
    When I click on menu item "Series"
    And the app switches to the "Series" view
    And I click on list item "Serie Grimmelshausen"
    Then the "Series" details page 1 for "Serie Grimmelshausen" appears
    # Action: MainActions::seriesDetailsSlice, GET /series/{id}/ (main-serie-v2); legacy GET /series/{id}/{page}/ (main-serie-v1).
    # PHPUnit route/render smoke coverage: tests/Actions/MainActionsTest.php::testAppGetRequest (/series/{id}/)

  Scenario: The next button shows the remaining series
    When I click on menu item "Series"
    And the app switches to the "Series" view
    And I click on the "next" button
    Then "Series" page 1 with content "Serie Rilke" appears
    And the list contains 1 item
    And there are "previous" buttons
    And there are no "next" buttons
    # Action: MainActions::seriesSlice, GET /series/ (main-series-v2); legacy GET /serieslist/{page}/ (main-series-v1).
    # Underlying pagination: tests/Calibre/CalibreTest.php::testSeriesSlice.
    # No MainActions test asserts page navigation or rendered controls.

  Scenario: The previous button returns to the first page
    When I click on menu item "Series"
    And the app switches to the "Series" view
    And I go to the "Series" page 1
    And I click on the "previous" button
    Then "Series" page 0 with content "Serie Lessing" appears
    And the list contains 2 items
    # Action: MainActions::seriesSlice, GET /series/ (main-series-v2); legacy GET /serieslist/{page}/ (main-series-v1).
    # Underlying pagination: tests/Calibre/CalibreTest.php::testSeriesSlice.
    # No MainActions test asserts page navigation or rendered controls.

  Scenario: Searching from the series list opens global search
    When I click on menu item "Series"
    And the app switches to the "Series" view
    And I enter "sto" into the "series" search field
    Then the search result page appears
    And the page contains "Books: 1"
    And the page contains "Authors: 1"
    And the page contains "Tags: 0"
    And the page contains "Series: 0"
    # Action: MainActions::globalSearch, GET/POST /search/ (main-search, main-search-post).
    # Underlying category search: tests/Calibre/CalibreTest.php::testTitlesSliceSearch,
    # testAuthorsSliceSearch, testTagsSliceSearch and testSeriesSliceSearch.
    # No MainActions test asserts the rendered global-search result page.
