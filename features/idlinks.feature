Feature: ID links

  If ID templates are active, book pages show links that transform a
  book ID (ISBN, Amazon, Google, ...) into an HTTP link.

  Background: Navigating to the titles list
  When I click on menu item "Books"
  And the app switches to the "Books" view

  Scenario: Book pages with suitable IDs contain ID links
  When I enter the id template data "ISBN search", "http://isbnsearch.org/isbn/%id%" for id "test1"
  And I navigate to title details page "6"
  Then there are 1 id links
  And the id links contain label "ISBN search" and url "http://isbnsearch.org/isbn/neuesleben1"
  # Actions: AdminActions::modifyIdTemplate PUT /admin/idtemplates/{id}/ (admin-idtemplate-put); MainActions::title GET /titles/{id}/ (main-title).
  # Related ID-template model coverage: tests/AppData/IdTemplatesTest.php::testIdTemplates.
  # No PHPUnit test currently asserts rendered ID-link substitution.

  Scenario: Book pages without suitable IDs contain no ID links
  When I enter the id template data "ISBN search", "http://isbnsearch.org/isbn/%id%" for id "test1"
  And I navigate to title details page "5"
  Then there are 0 id links
  # Actions: AdminActions::modifyIdTemplate PUT /admin/idtemplates/{id}/ (admin-idtemplate-put); MainActions::title GET /titles/{id}/ (main-title).
  # No PHPUnit test currently asserts rendered ID-link visibility.
