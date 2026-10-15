Feature: Delete id template
  # Client flow: script.js handles .idtemplate_clear with a DELETE XHR.
  # No tests/js harness currently covers ID-template deletion.

  An admin user can delete defined id templates in the administration
  configuration's ID Templates view.

  Background: Navigating to the ID Templates view
  When I click on menu item "ID Templates"
  And the app switches to the "ID Templates" view

  Scenario: Delete id template
  When I enter the id template data "ISBN search", "http://isbnsearch.org/isbn/%id%" for id "test1"
  Then I get a success message "Changes applied"
  And the id template "test1" contains label "ISBN search" and URL "http://isbnsearch.org/isbn/%id%"
  When I delete id template "test1"
  Then I get a success message "Changes applied"
  # Actions: AdminActions::modifyIdTemplate, PUT /admin/idtemplates/{id}/ (admin-idtemplate-put); AdminActions::clearIdTemplate, DELETE /admin/idtemplates/{id}/ (admin-idtemplate-delete).
  # covered-by: tests/Actions/AdminActionsTest.php testClearIdTemplate
  #            tests/AppData/IdTemplatesTest.php testDeleteIdTemplates
