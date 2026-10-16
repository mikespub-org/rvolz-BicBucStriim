Feature: Series metadata

  Admin metadata endpoints support notes and links associated with a series.
  # Separate EPUB Loader callback coverage: tests/Actions/ExtraActionsTest.php::testMetadataCallbacksAddLinks checks link creation for author, series, and book callbacks; it does not exercise the loader request flow.

  Background: Acting as an administrator
    Given I am logged in as "admin"

  Scenario: Add and delete a Markdown series note
    When I request series notes for series "6"
    Then the series note is empty
    When I save the Markdown note "## Reading notes" for series "6"
    Then the response contains the rendered heading "Reading notes"
    And the response message is "Changes applied"
    When I delete the note for series "6"
    Then the series note is empty
    # Actions: MetadataActions::getSeriesNote GET /metadata/series/{id}/notes/ (meta-series-note); editSeriesNotes POST to the same path (meta-series-note-post); delSeriesNotes DELETE to the same path (meta-series-note-delete).
    # covered-by: tests/Actions/MetadataActionsTest.php::testSeriesNote.

  Scenario: Add and delete a series link
    When I request links for series "6"
    Then the series link list is empty
    When I add the link "https://example.org" with label "Publisher" to series "6"
    Then the response message is "Changes applied"
    And the response contains the new link identifier
    When I delete that link from series "6"
    Then the series link list is empty
    # Actions: MetadataActions::getSeriesLinks GET /metadata/series/{id}/links/ (meta-series-links); newSeriesLink POST to the same path (meta-series-link-post); delSeriesLink DELETE /metadata/series/{id}/links/{link}/ (meta-series-link-delete).
    # covered-by: tests/Actions/MetadataActionsTest.php::testSeriesLinks.
