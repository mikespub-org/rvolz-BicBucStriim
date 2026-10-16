Feature: Redirect after installation

  After a fresh install the application redirects to the admin page.

  Scenario: Root redirects to admin page
  Given I login as user "admin"
  When I visit "/"
  Then I get redirected to the admin page
  # Action: MainActions::main, GET / (main-home); the install-state redirect is middleware/config behavior.
  # Related authentication redirect coverage: tests/Middleware/MiddlewareTest.php::testMustLoginNoAuth.
  # No PHPUnit test currently asserts the post-install redirect destination.

  Scenario: Every other page also redirects to admin page
  Given I login as user "admin"
  When I visit "/titles/"
  Then I get redirected to the admin page
  # Action: MainActions::main, GET / (main-home); the install-state redirect is middleware/config behavior.
  # Related authentication redirect coverage: tests/Middleware/MiddlewareTest.php::testMustLoginNoAuth.
  # No PHPUnit test currently asserts the post-install redirect destination.
