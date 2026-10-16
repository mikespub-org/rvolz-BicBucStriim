Feature: Tag details pages list the books for a single tag.

  Scenario: A tag details page lists books for a single tag
    When I click on menu item "Tags"
    And the app switches to the "Tags" view
    And I click on list item "Architecture"
    Then the "Tags" details page 21 for "Architecture" appears
    And the list contains 1 item
    # Action: MainActions::tagDetailsSlice, GET /tags/{id}/ (main-tag-v2); legacy GET /tags/{id}/{page}/ (main-tag-v1).
    # PHPUnit route/render smoke coverage: tests/Actions/MainActionsTest.php::testAppGetRequest (/tags/{id}/).
