Feature: SMTP configuration

  An admin can configure the SMTP account used by the application mailer.

  Background: Navigating to SMTP configuration
    When I click on menu item "Home"
    And the app switches to the "Home" view
    And I click on menu item "Admin"
    And the app switches to the "Admin" view
    And I click on menu item "Mail"
    And the app switches to the "Mail configuration" view

  Scenario: Save SMTP settings
    When I enter SMTP username "testuser"
    And I enter SMTP password "testpassword"
    And I enter SMTP server "smtp.example.org"
    And I enter SMTP port 587
    And I select SMTP encryption "TLS"
    And I save the mail configuration
    Then the mail configuration shows server "smtp.example.org"
    And it shows port 587 and encryption "TLS"
    When I reload the mail configuration page
    Then the mail configuration still shows server "smtp.example.org"
    And it still shows port 587 and encryption "TLS"
    # Actions: AdminActions::getSmtpConfig, GET /admin/mail/ (admin-smtp-config); AdminActions::changeSmtpConfig, PUT /admin/mail/ (admin-smtp-config-put).
    # Page and update endpoint coverage: tests/Actions/AdminActionsTest.php::testGetSmtpConfig
    # and tests/Actions/AdminActionsTest.php::testChangeSmtpConfig.
