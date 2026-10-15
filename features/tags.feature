Feature: The tags list displays all book tags in the library.

  The tags list is ordered by name and paginated according to the
  configuration settings.

  Scenario: The Tags menu shows the first page
    When I click on menu item "Tags"
    Then the app switches to the "Tags" view
    And the list contains 2 items
    And the menu item "Tags" is active
    And there are no "previous" buttons
    And there are "next" buttons
    # Action: MainActions::tagsSlice, GET /tags/ (main-tags-v2); legacy GET /tagslist/{page}/ (main-tags-v1).
    # PHPUnit route/render smoke coverage: tests/Actions/MainActionsTest.php::testAppGetRequest (/tags/).

  Scenario: Clicking a tag opens its details page
    When I click on menu item "Tags"
    And the app switches to the "Tags" view
    And I click on list item "Architecture"
    Then the "Tags" details page 21 for "Architecture" appears
    # Action: MainActions::tagDetailsSlice, GET /tags/{id}/ (main-tag-v2); legacy GET /tags/{id}/{page}/ (main-tag-v1).
    # PHPUnit route/render smoke coverage: tests/Actions/MainActionsTest.php::testAppGetRequest (/tags/{id}/).

  Scenario: The next button shows the next page of tags
    When I click on menu item "Tags"
    And the app switches to the "Tags" view
    And I click on the "next" button
    Then "Tags" page 1 with content "Biografien & Memoiren" appears
    And the list contains 2 items
    And there are "previous" buttons
    And there are "next" buttons
    # Action: MainActions::tagsSlice, GET /tags/ (main-tags-v2); legacy GET /tagslist/{page}/ (main-tags-v1).
    # Underlying pagination: tests/Calibre/CalibreTest.php::testTagsSlice.
    # No MainActions test asserts page navigation or rendered controls.

  Scenario: The previous button returns to the first page
    When I click on menu item "Tags"
    And the app switches to the "Tags" view
    And I go to the "Tags" page 1
    And I click on the "previous" button
    Then "Tags" page 0 with content "Architecture" appears
    And the list contains 2 items
    # Action: MainActions::tagsSlice, GET /tags/ (main-tags-v2); legacy GET /tagslist/{page}/ (main-tags-v1).
    # Underlying pagination: tests/Calibre/CalibreTest.php::testTagsSlice.
    # No MainActions test asserts page navigation or rendered controls.

  Scenario: Searching from the tags list opens global search
    When I click on menu item "Tags"
    And the app switches to the "Tags" view
    And I enter "sto" into the "tags" search field
    Then the search result page appears
    And the page contains "Books: 1"
    And the page contains "Authors: 1"
    And the page contains "Tags: 0"
    And the page contains "Series: 0"
    # Action: MainActions::globalSearch, GET/POST /search/ (main-search, main-search-post).
    # Underlying category search: tests/Calibre/CalibreTest.php::testTitlesSliceSearch,
    # testAuthorsSliceSearch, testTagsSliceSearch and testSeriesSliceSearch.
    # No MainActions test asserts the rendered global-search result page.
