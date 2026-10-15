Feature: Replace author images
  # Image upload uses a native multipart form; script.js only opens the metadata panels.
  # No tests/js harness currently covers panel opening or image replacement.

  Replacing an author image uploads a new image for the same author.

  Background: An author already has an image
    When I click on menu item "Authors"
    And the app switches to the "Authors" view
    And I click on list item "Grimmelshausen"
    And the "Authors" details page 6 for "Grimmelshausen" appears
    And I click on the metadata menu
    And I click on the image menu button
    And the author image panel appears
    And I attach the author image file "tests/fixtures/author1.jpg"
    And I press the "Upload" button
    Then there is an author image

  Scenario: Replace an existing author image
    When I attach the author image file "tests/fixtures/test-cover.jpg"
    And I press the "Upload" button
    Then the displayed author image has been replaced with the new upload
    # Action: MetadataActions::editAuthorThumbnail, POST /metadata/authors/{id}/thumbnail/ (meta-author-thumb-post).
    # Upload path coverage: tests/Actions/MetadataActionsTest.php::testEditAuthorThumbnail.
    # No PHPUnit test currently verifies replacement of an existing image.
