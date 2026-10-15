Feature: Logout

  An authenticated user can end the current session.

  Scenario: Logging out ends the session
    Given I am logged in as "admin"
    When I open the logout page
    Then the logout page is displayed
    And I am no longer authenticated
    # Action: MainActions::logout, GET /logout/ (main-logout).
    # Route/render coverage: tests/Actions/MainActionsTest.php::testAppGetRequest (/logout/).
    # No PHPUnit test currently verifies that the session is cleared.
