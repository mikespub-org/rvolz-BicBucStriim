Feature: Delete user
  # Client flow: script.js handles .user_delete, confirmation, DELETE XHR, and list removal.
  # No tests/js harness currently covers user deletion.

  An admin user can delete an existing user in the administration Users view.
  The admin user account cannot be deleted.

  Background: Navigating to the users page
  When I click on menu item "Home"
  And the app switches to the "Home" view
  And I click on menu item "Admin"
  And the app switches to the "Admin" view
  And I click on menu item "Users"
  And the app switches to the "Users" view

  Scenario: Delete normal user
  When I add a user account with name "testuser7" and password "testuser_pw"
  Then I get a success message "Changes applied"
  And the user list contains "testuser7"
  When I delete user "testuser7"
  And I confirm the deletion
  Then I get a success message "Changes applied"
  And the user list doesn't contain "testuser7"
  # Actions: AdminActions::addUser, POST /admin/users/ (admin-users-post); AdminActions::deleteUser, DELETE /admin/users/{id}/ (admin-user-delete).
  # covered-by: tests/Actions/AdminActionsTest.php testGetUsers
  #            tests/AppData/UsersTest.php testDeleteUser (delete bean id 3)
  #            src/Traits/HasUsers.php deleteUser (bean id 1 protected)

  Scenario: Deleting the admin user is not possible
  When I add a user account with name "testuser8" and password "testuser_pw"
  Then I get a success message "Changes applied"
  And the user list contains "testuser8"
  When I delete user "admin"
  And I confirm the deletion
  Then I get the error message "Error while applying changes"
  And the user list contains "admin"
  # Action: AdminActions::deleteUser, DELETE /admin/users/{id}/ (admin-user-delete).
  # The admin account (RedBean bean id 1) is protected in
  # src/Traits/HasUsers.php deleteUser(). No dedicated PHPUnit test needed;
  # the behaviour is enforced in the backend trait.
  # covered-by: src/Traits/HasUsers.php deleteUser()
