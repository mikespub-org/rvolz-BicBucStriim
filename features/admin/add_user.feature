Feature: Add user
  # Client flow: script.js handles #newuserform with XHR and inserts the returned user.
  # No tests/js harness currently covers user creation.

  An admin user can add new users in the admin/users page.
  A new user is defined by a unique name and a non-empty password.

  Background: Navigating to the users page
  When I click on menu item "Home"
  And the app switches to the "Home" view
  And I click on menu item "Admin"
  And the app switches to the "Admin" view
  And I click on menu item "Users"
  And the app switches to the "Users" view

  Scenario: Add valid user
  When I enter the user credentials "testuser", "testuser_pw"
  Then I get the success message "Changes applied"
  And the list contains 2 items
  And the list contains user "testuser"
  # Action: AdminActions::addUser, POST /admin/users/ (admin-users-post); AdminActions::getUsers, GET /admin/users/ (admin-users).
  # covered-by: tests/Actions/AdminActionsTest.php testGetUsers /
  #            testAddUserValid (JSON /admin/users/ POST, nonempty name+password)
  #            tests/AppData/UsersTest.php testAddUser (RedBean trait, empty)
  #            tests/AppData/UsersTest.php testAddUserEmptyUser

  Scenario: Add valid user with unicode in user name
  When I enter the user credentials "testüser", "testuser_pw"
  Then I get the success message "Changes applied"
  And the list contains 2 items
  And the list contains user "testüser"
  # Action: AdminActions::addUser, POST /admin/users/ (admin-users-post).
  # covered-by: tests/AppData/UsersTest.php testAddUser (backend, username is
  #            a string; no token-escape assumption in the trait)

  Scenario: Add user with existing user name
  When I enter the user credentials "testuser2", "testuser2_pw"
  And I get the success message "Changes applied"
  And I enter the user credentials "testuser2", "testuser3_pw"
  Then I get the success message "Error while applying changes"
  # Action: AdminActions::addUser, POST /admin/users/ (admin-users-post).
  # covered-by: tests/AppData/UsersTest.php testAddUser (duplicate username →
  #            trait returns null) / tests/Actions/AdminActionsTest.php
  #            testAddUserInvalid (backend returns 500 + error message)

  Scenario: Add user with empty user name
  When I enter the user credentials "", "testuser2_pw"
  Then I get the error message "Error while applying changes"
  # Action: AdminActions::addUser, POST /admin/users/ (admin-users-post).
  # covered-by: tests/AppData/UsersTest.php testAddUserEmptyUsername
  #            tests/Actions/AdminActionsTest.php testAddUserInvalid

  Scenario: Add user with empty password
  When I enter the user credentials "testuser3", ""
  Then I get the error message "Error while applying changes"
  # Action: AdminActions::addUser, POST /admin/users/ (admin-users-post).
  # covered-by: tests/AppData/UsersTest.php testAddUserEmptyPassword
  #            tests/Actions/AdminActionsTest.php testAddUserInvalid

  Scenario: Add user with empty user credentials
  When I enter the user credentials "", ""
  Then I get the error message "Error while applying changes"
  # Action: AdminActions::addUser, POST /admin/users/ (admin-users-post).
  # covered-by: tests/AppData/UsersTest.php testAddUserEmptyUser
  #            tests/Actions/AdminActionsTest.php testAddUserInvalid
