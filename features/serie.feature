Feature: Series details pages list the books for a single series.

  Scenario: A series details page lists books for a single series
    When I click on menu item "Series"
    And the app switches to the "Series" view
    And I click on list item "Serie Grimmelshausen"
    Then the "Series" details page 1 for "Serie Grimmelshausen" appears
    And the list contains 2 items
    # Action: MainActions::seriesDetailsSlice, GET /series/{id}/ (main-serie-v2); legacy GET /series/{id}/{page}/ (main-serie-v1).
    # PHPUnit route/render smoke coverage: tests/Actions/MainActionsTest.php::testAppGetRequest (/series/{id}/).
