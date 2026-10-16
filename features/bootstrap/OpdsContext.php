<?php

use Behat\Step\Then;
use Behat\Step\When;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;

/**
 * Defines application features from the specific context.
 */
class OpdsContext implements Context
{
    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct() {}

    #[When('I request the OPDS catalog :arg1')]
    public function iRequestTheOpdsCatalog($arg1): void
    {
        throw new PendingException();
    }

    #[Then('the catalog contains links for :arg1, :arg2, :arg3, :arg4 and :arg5')]
    public function theCatalogContainsLinksForAnd($arg1, $arg2, $arg3, $arg4, $arg5): void
    {
        throw new PendingException();
    }

    #[Then('the acquisition catalog is titled :arg1')]
    public function theAcquisitionCatalogIsTitled($arg1): void
    {
        throw new PendingException();
    }

    #[Then('its book entries have acquisition links')]
    public function itsBookEntriesHaveAcquisitionLinks(): void
    {
        throw new PendingException();
    }

    #[When('I follow the author-initial link :arg1')]
    public function iFollowTheAuthorInitialLink($arg1): void
    {
        throw new PendingException();
    }

    #[When('I follow the author link :arg1')]
    public function iFollowTheAuthorLink($arg1): void
    {
        throw new PendingException();
    }

    #[Then('the acquisition catalog lists books by :arg1')]
    public function theAcquisitionCatalogListsBooksBy($arg1): void
    {
        throw new PendingException();
    }

    #[When('I follow the tag-initial link :arg1')]
    public function iFollowTheTagInitialLink($arg1): void
    {
        throw new PendingException();
    }

    #[When('I follow the tag link :arg1')]
    public function iFollowTheTagLink($arg1): void
    {
        throw new PendingException();
    }

    #[Then('the acquisition catalog lists books tagged :arg1')]
    public function theAcquisitionCatalogListsBooksTagged($arg1): void
    {
        throw new PendingException();
    }

    #[When('I follow the series-initial link :arg1')]
    public function iFollowTheSeriesInitialLink($arg1): void
    {
        throw new PendingException();
    }

    #[When('I follow the series link :arg1')]
    public function iFollowTheSeriesLink($arg1): void
    {
        throw new PendingException();
    }

    #[Then('the acquisition catalog lists books in :arg1')]
    public function theAcquisitionCatalogListsBooksIn($arg1): void
    {
        throw new PendingException();
    }

    #[When('I request the OPDS document :arg1')]
    public function iRequestTheOpdsDocument($arg1): void
    {
        throw new PendingException();
    }

    #[Then('the document describes the OPDS search endpoint')]
    public function theDocumentDescribesTheOpdsSearchEndpoint(): void
    {
        throw new PendingException();
    }

    #[When('I request the OPDS search catalog for :arg1')]
    public function iRequestTheOpdsSearchCatalogFor($arg1): void
    {
        throw new PendingException();
    }

    #[Then('the acquisition catalog contains the matching book')]
    public function theAcquisitionCatalogContainsTheMatchingBook(): void
    {
        throw new PendingException();
    }

    #[When('I request the OPDS search catalog without a query')]
    public function iRequestTheOpdsSearchCatalogWithoutAQuery(): void
    {
        throw new PendingException();
    }

    #[Then('the response status is :arg1')]
    public function theResponseStatusIs($arg1): void
    {
        throw new PendingException();
    }
}
