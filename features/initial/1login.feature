Feature: Login

  Access to the application requires valid login data.

  Scenario: The login page renders
  Then I get the login page
  # Action: MainActions::showLogin, GET /login/ (main-login).
  # Route coverage: tests/Actions/MainActionsTest.php::testAppGetRequest (/login/).

  Scenario: Invalid login
  When I login as user "noadmin"
  Then I get the login page
  # Action: MainActions::performLogin, POST /login/ (main-login-post).
  # No PHPUnit test currently exercises invalid credentials through POST /login/.

  Scenario: Valid login
  When I login as user "admin"
  Then I get the installation page
  # Action: MainActions::performLogin, POST /login/ (main-login-post).
  # No PHPUnit test currently exercises valid credentials through POST /login/.
