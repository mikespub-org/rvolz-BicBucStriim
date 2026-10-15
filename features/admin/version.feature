Feature: Version check

  An admin can check whether a newer application version is available.

  Background: Navigating to the version page
    When I click on menu item "Home"
    And the app switches to the "Home" view
    And I click on menu item "Admin"
    And the app switches to the "Admin" view
    And I open the version check page

  Scenario: Show a version check result
    Then the page displays the current version and a check result
    # Action: AdminActions::checkVersion, GET /admin/version/ (admin-check-version).
    # The check may report an available update, that this version is current,
    # or a fetch error. No PHPUnit test currently covers this endpoint.
