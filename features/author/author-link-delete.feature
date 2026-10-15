Feature: Author links delete
  # Client flow: script.js handles .author-link-delete and removes the link from both lists.
  # tests/js/author_links_harness.js (driven by AuthorLinksScriptTest::testLinksEditorScriptBehaviour)
  # asserts the DELETE request and DOM updates.

  An admin user can delete an author link on the author details page.

  Background: Navigating to an author details page
  When I click on menu item "Authors"
  And the app switches to the "Authors" view
  And I click on list item "Grimmelshausen"
  And the app switches to the "Author details" page 6 for "Grimmelshausen"
  And I click on the "Edit" button
  And the app switches to the "Author details" view

  Scenario: Author links can be deleted
  When I delete the author link "My Website"
  And I confirm the deletion
  Then I get a success message "Changes applied"
  And the author links list doesn't contain the link "My Website"
  # Action: MetadataActions::delAuthorLink, DELETE /metadata/authors/{id}/links/{link}/ (meta-author-link-delete).
  # covered-by: tests/Actions/MetadataActionsTest.php::testAuthorLinks
