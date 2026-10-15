Feature: OPDS catalogs and search

  The OPDS interface exposes navigation and acquisition catalogs for the
  library, plus OpenSearch-based title search.

  Scenario: The root catalog links to the available catalogs
    When I request the OPDS catalog "/opds/"
    Then the catalog contains links for "By Newest", "By Title", "By Author", "By Tag" and "By Series"
    # Action: OpdsActions::opdsRoot, GET /opds/ (opds-home).
    # Catalog generation: tests/Utilities/OpdsGeneratorTest.php::testRootCatalogValidation.

  Scenario: The newest catalog lists books with downloadable formats
    When I request the OPDS catalog "/opds/newest/"
    Then the acquisition catalog is titled "By Newest"
    And its book entries have acquisition links
    # Action: OpdsActions::opdsNewest, GET /opds/newest/ (opds-newest).
    # Catalog generation: tests/Utilities/OpdsGeneratorTest.php::testNewestCatalogValidation.

  Scenario: The title catalog lists a page of books
    When I request the OPDS catalog "/opds/titleslist/0/"
    Then the acquisition catalog is titled "By Title"
    And its book entries have acquisition links
    # Action: OpdsActions::opdsByTitle, GET /opds/titleslist/{page}/ (opds-title-page).
    # Catalog generation: tests/Utilities/OpdsGeneratorTest.php::testTitlesCatalogValidation.

  Scenario: Browse books by author
    When I request the OPDS catalog "/opds/authorslist/"
    And I follow the author-initial link "R"
    And I follow the author link "Rainer Maria Rilke"
    Then the acquisition catalog lists books by "Rainer Maria Rilke"
    # Actions: OpdsActions::opdsByAuthorInitial GET /opds/authorslist/ (opds-author-initials); opdsByAuthorNamesForInitial GET /opds/authorslist/{initial}/ (opds-author-names); opdsByAuthor GET /opds/authorslist/{initial}/{id}/{page}/ (opds-author-page).
    # Catalog generation: tests/Utilities/OpdsGeneratorTest.php::testAuthorsInitialCatalogValidation,
    # testAuthorsNamesForInitialCatalogValidation and testAuthorsBooksForAuthorCatalogValidation.

  Scenario: Browse books by tag
    When I request the OPDS catalog "/opds/tagslist/"
    And I follow the tag-initial link "A"
    And I follow the tag link "Architecture"
    Then the acquisition catalog lists books tagged "Architecture"
    # Actions: OpdsActions::opdsByTagInitial GET /opds/tagslist/ (opds-tag-initials); opdsByTagNamesForInitial GET /opds/tagslist/{initial}/ (opds-tag-names); opdsByTag GET /opds/tagslist/{initial}/{id}/{page}/ (opds-tag-page).
    # Catalog generation: tests/Utilities/OpdsGeneratorTest.php::testTagsInitialCatalogValidation,
    # testTagsNamesForInitialCatalogValidation and testTagsBooksForTagCatalogValidation.

  Scenario: Browse books by series
    When I request the OPDS catalog "/opds/serieslist/"
    And I follow the series-initial link "S"
    And I follow the series link "Serie Grimmelshausen"
    Then the acquisition catalog lists books in "Serie Grimmelshausen"
    # Actions: OpdsActions::opdsBySeriesInitial GET /opds/serieslist/ (opds-series-initials); opdsBySeriesNamesForInitial GET /opds/serieslist/{initial}/ (opds-series-names); opdsBySeries GET /opds/serieslist/{initial}/{id}/{page}/ (opds-series-page).
    # Route coverage: tests/Actions/OpdsActionsTest.php::testAppGetRequest.
    # The PHPUnit suite has no public test for the generated series book entries.

  Scenario: The OpenSearch descriptor links to the search catalog
    When I request the OPDS document "/opds/opensearch.xml"
    Then the document describes the OPDS search endpoint
    # Action: OpdsActions::opdsSearchDescriptor, GET /opds/opensearch.xml (opds-opensearch).
    # Descriptor coverage: tests/Utilities/OpdsGeneratorTest.php::testTitlesCatalogOpenSearch.

  Scenario: Search the OPDS catalog by title
    When I request the OPDS search catalog for "Lob der Faulheit"
    Then the acquisition catalog contains the matching book
    # Action: OpdsActions::opdsBySearch, GET /opds/searchlist/{page}/?search={search} (opds-search-page).
    # Underlying title filtering: tests/Calibre/CalibreTest.php::testTitlesSliceSearch.
    # No OPDS action test asserts the rendered matching entry.

  Scenario: A search request without a query is rejected
    When I request the OPDS search catalog without a query
    Then the response status is 400
    # Action: OpdsActions::opdsBySearch, GET /opds/searchlist/{page}/ (opds-search-page).
    # covered-by: tests/Actions/OpdsActionsTest.php::testOpdsSearchMissing.
