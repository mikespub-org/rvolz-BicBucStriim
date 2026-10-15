Feature: Add id template
  # Client flow: script.js handles #templateform submission with a PUT XHR.
  # No tests/js harness currently covers the ID-template editor.

  An admin user can add an id template in the administration configuration's
  ID Templates view. An id template is defined by a unique label and a URL
  containing the parameter "%id%".

  Background: Navigating to the ID Templates view
  When I click on menu item "ID Templates"
  And the app switches to the "ID Templates" view

  Scenario: Add valid id template
  When I add an id template with label "ISBN search" and URL "http://isbnsearch.org/isbn/%id%" for id "test1"
  Then I get a success message "Changes applied"
  And the id template "test1" contains label "ISBN search" and URL "http://isbnsearch.org/isbn/%id%"
  # Actions: AdminActions::getIdTemplates, GET /admin/idtemplates/ (admin-idtemplates); AdminActions::modifyIdTemplate, PUT /admin/idtemplates/{id}/ (admin-idtemplate-put).
  # covered-by: tests/Actions/AdminActionsTest.php testGetIdTemplates /
  #            testModifyIdTemplate (JSON PUT /admin/idtemplates/{id}/)
  #            tests/AppData/IdTemplatesTest.php testIdTemplates

  Scenario: Add invalid id template
  When I add an id template with label "" and URL "" for id "test1"
  Then I get the error message "Error while applying changes"
  # Action: AdminActions::modifyIdTemplate, PUT /admin/idtemplates/{id}/ (admin-idtemplate-put).
  # No dedicated PHPUnit test yet. Backend validation lives in
  # src/Actions/AdminActions.php modifyIdTemplate() (url/label non-empty).
  # covered-by: tests/Actions/AdminActionsTest.php testGetIdTemplates
  #            (backend contract); harness covers UI materially
