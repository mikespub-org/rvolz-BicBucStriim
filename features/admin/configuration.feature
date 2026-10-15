Feature: Application configuration

  An admin can save application-wide settings and the server validates
  settings that have required bounds or dependencies.

  Background: Navigating to the configuration page
    When I click on menu item "Home"
    And the app switches to the "Home" view
    And I click on menu item "Admin"
    And the app switches to the "Admin" view
    And I click on menu item "Configuration"
    And the app switches to the "Configuration" view

  Scenario: Save the title date sort and page size
    When I set the title date sort to "Publication date"
    And I set the page size to 5
    And I save the configuration
    Then the title date sort is "Publication date"
    And the page size is 5
    # Actions: AdminActions::configuration, GET /admin/configuration/ (admin-config); AdminActions::changeJson, POST /admin/configuration/ (admin-config-post).
    # No PHPUnit test currently exercises AdminActions::changeJson persistence.

  Scenario: Reject a page size outside the allowed range
    When I set the page size to 0
    And I save the configuration
    Then the configuration is not saved
    And I see a page-size validation error
    # Action: AdminActions::changeJson, POST /admin/configuration/ (admin-config-post).
    # AdminActions::changeJson accepts page sizes from 1 through 100.

  Scenario: Require a sender address when enabling Kindle
    When I enable Send-to-Kindle
    And I clear the Kindle sender email
    And I save the configuration
    Then the configuration is not saved
    And I see a sender-email validation error
    # Action: AdminActions::changeJson, POST /admin/configuration/ (admin-config-post).
    # AdminActions::changeJson validates the sender email when Kindle is enabled.
