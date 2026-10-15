Feature: Admin configures after installation

  As an admin user I get the admin page immediately and can enter the
  Calibre library directory.

  Scenario: Invalid directory
  When I enter an invalid directory name
  Then I get an error message
  # Actions: AdminActions::configuration, GET /admin/configuration/ (admin-config); AdminActions::changeJson, POST /admin/configuration/ (admin-config-post).
  # Configuration page coverage: tests/Actions/AdminActionsTest.php::testConfiguration.

  Scenario: Valid directory
  When I enter a valid directory name
  Then the application saves the configuration
  And I can navigate to the home page
  # Actions: AdminActions::configuration, GET /admin/configuration/ (admin-config); AdminActions::changeJson, POST /admin/configuration/ (admin-config-post).
  # Related settings coverage: tests/Actions/AdminActionsTest.php::testConfiguration
  # and tests/AppData/BicBucStriimTest.php::testConfigs.
