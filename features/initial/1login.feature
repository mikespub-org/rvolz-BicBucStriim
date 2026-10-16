Feature: Login

  Access to the application requires valid login data.

  Scenario: The login page renders
  When I visit "/login/"
  Then I get the login page
  # Action: MainActions::showLogin, GET /login/ (main-login).
  # Route coverage: tests/Actions/MainActionsTest.php::testAppGetRequest (/login/).

  Scenario: Invalid login
  When I login as user "noadmin"
  Then I get the login page
  # Action: MainActions::performLogin, POST /login/ (main-login-post).
  # PHPUnit action-level coverage: tests/Actions/MainActionsTest.php::testPerformLoginRendersLoginPageWhenAuthenticationFails simulates an authentication exception; it does not verify real credential validation.

  Scenario: Valid login
  When I login as user "admin"
  Then I get the installation page
  # Action: MainActions::performLogin, POST /login/ (main-login-post).
  # PHPUnit action-level coverage: tests/Actions/MainActionsTest.php::testPerformLoginRedirectsAfterSuccessfulAuthentication verifies the /bbs/ redirect with mocked authentication.
