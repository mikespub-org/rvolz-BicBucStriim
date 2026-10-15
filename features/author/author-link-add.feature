Feature: Author link add
  # Client flow: script.js handles #author-link-new submission and updates both link lists.
  # tests/js/author_links_harness.js (driven by AuthorLinksScriptTest::testLinksEditorScriptBehaviour)
  # asserts the POST request, DOM updates, and failure behavior.

  An admin user can add a link to an author's website, blog, Twitter feed or
  similar on the author details page.

  Background: Navigating to an author details page
  When I click on menu item "Authors"
  And the app switches to the "Authors" view
  And I click on list item "Grimmelshausen"
  And the app switches to the "Author details" page 6 for "Grimmelshausen"
  And I click on the "Edit" button
  And the app switches to the "Author details" view

  Scenario: Author links can be added
  When I add an author link with label "My Website" and URL "https://example.org"
  Then I get a success message "Changes applied"
  And the author links list contains the link "https://example.org" with label "My Website"
  # Action: MetadataActions::newAuthorLink, POST /metadata/authors/{id}/links/ (meta-author-link-post).
  # covered-by: tests/Actions/MetadataActionsTest.php::testAuthorLinks
  #            (JSON POST /authors/{id}/links/) and
  #            tests/AppData/AuthorsTest.php testAuthorLinks
