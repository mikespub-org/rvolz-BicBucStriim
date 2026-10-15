Feature: Edit user
  # Client flow: script.js handles #userform submission with a PUT XHR.
  # No tests/js harness currently covers user editing.

  An admin can change an existing user's password, role, language filter,
  and tag filter in the Users view.

  Background: Navigating to the users page
  When I click on menu item "Home"
  And the app switches to the "Home" view
  And I click on menu item "Admin"
  And the app switches to the "Admin" view
  And I click on menu item "Users"
  And the app switches to the "Users" view

  Scenario: Change password
  When I add a user account with name "testuser9" and password "testuser_pw"
  Then I get a success message "Changes applied"
  And the user list contains "testuser9"
  When I edit user "testuser9" and change the password to "testuser_other_pw"
  Then I get a success message "Changes applied"
  # Actions: AdminActions::getUser, GET /admin/users/{id}/ (admin-user); AdminActions::modifyUser, PUT /admin/users/{id}/ (admin-user-put).
  # covered-by: tests/Actions/AdminActionsTest.php testGetUser
  #            tests/AppData/UsersTest.php testChangeUser

  Scenario: Empty passwords cannot be used
  When I add a user account with name "testuser9" and password "testuser_pw"
  Then I get a success message "Changes applied"
  And the user list contains "testuser9"
  When I edit user "testuser9" and change the password to ""
  Then I get the error message "Error while applying changes"
  # Action: AdminActions::modifyUser, PUT /admin/users/{id}/ (admin-user-put).
  # covered-by: tests/AppData/UsersTest.php testChangeUser (empty password → null)

  Scenario: Change the language filter
  When I add a user account with name "testuser10" and password "testuser_pw"
  Then I get a success message "Changes applied"
  And the user list contains "testuser10"
  When I edit user "testuser10" and change the language filter to "deu"
  Then I get a success message "Changes applied"
  # Action: AdminActions::modifyUser, PUT /admin/users/{id}/ (admin-user-put).
  # covered-by: tests/AppData/UsersTest.php testChangeUser (languages='deu')

  Scenario: Change the tags filter
  When I add a user account with name "testuser11" and password "testuser_pw"
  Then I get a success message "Changes applied"
  And the user list contains "testuser11"
  When I edit user "testuser11" and change the tag filter to "poetry"
  Then I get a success message "Changes applied"
  # Action: AdminActions::modifyUser, PUT /admin/users/{id}/ (admin-user-put).
  # covered-by: tests/AppData/UsersTest.php testChangeUser (tags='poetry')

  Scenario: Change a user's role
  When I add a user account with name "testuser12" and password "testuser_pw"
  And I edit user "testuser12" and change the role to "admin"
  Then the user "testuser12" has the "admin" role
  # Action: AdminActions::modifyUser, PUT /admin/users/{id}/ (admin-user-put).
  # covered-by: tests/Actions/AdminActionsTest.php::testModifyUserValid
