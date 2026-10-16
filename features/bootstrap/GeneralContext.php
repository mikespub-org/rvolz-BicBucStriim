<?php

use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\MinkExtension\Context\RawMinkContext;

/**
 * Defines application features from the specific context.
 */
class GeneralContext extends RawMinkContext
{
    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct() {}

    #[When('I click on menu item :arg1')]
    public function iClickOnMenuItem($arg1): void
    {
        try {
            $this->getSession()->getCurrentUrl();
        } catch (\Behat\Mink\Exception\DriverException) {
            $this->visitPath('/');
        }

        $link = $this->getSession()->getPage()->findLink($arg1);
        if ($link === null) {
            throw new \RuntimeException(sprintf('The menu link "%s" was not found.', $arg1));
        }
        $link->click();
    }

    #[When('the app switches to the :arg1 view')]
    public function theAppSwitchesToTheView($arg1): void
    {
        $this->assertSession()->statusCodeEquals(200);
        $this->theMenuItemIsActive($arg1);
    }

    #[Then('the list contains :arg1 items')]
    public function theListContainsItems($arg1): void
    {
        $this->assertSession()->elementsCount('css', '.bbs-list-item', (int) $arg1);
    }

    #[When('I enter the id template data :arg1, :arg2 for id :arg3')]
    public function iEnterTheIdTemplateDataForId($arg1, $arg2, $arg3): void
    {
        throw new PendingException();
    }

    #[Then('the list contains :arg1 item')]
    public function theListContainsItem($arg1): void
    {
        $this->assertSession()->elementsCount('css', '.bbs-list-item', (int) $arg1);
    }

    #[When('I click on list item :arg1')]
    public function iClickOnListItem($arg1): void
    {
        throw new PendingException();
    }

    #[When('the :arg1 details page :arg3 for :arg2 appears')]
    public function theDetailsPageForAppears($arg1, $arg2, $arg3): void
    {
        throw new PendingException();
    }

    #[When('I click on the :arg1 button')]
    public function iClickOnTheButton($arg1): void
    {
        $links = $this->matchingSubnavLinks($arg1);
        if ($links === []) {
            throw new \RuntimeException(sprintf('The "%s" pagination link was not found.', $arg1));
        }
        $links[0]->click();
    }

    #[Then('the menu item :arg1 is active')]
    public function theMenuItemIsActive($arg1): void
    {
        $link = $this->getSession()->getPage()->findLink($arg1);
        if ($link === null) {
            throw new \RuntimeException(sprintf('The "%s" menu link was not found.', $arg1));
        }
        if (!preg_match('/(?:^|\s)active(?:\s|$)/', (string) $link->getAttribute('class'))) {
            throw new \RuntimeException(sprintf('The "%s" menu link is not active.', $arg1));
        }
    }

    #[Then('there are no :arg1 buttons')]
    public function thereAreNoButtons($arg1): void
    {
        if ($this->matchingSubnavLinks($arg1) !== []) {
            throw new \RuntimeException(sprintf('Expected no "%s" pagination link.', $arg1));
        }
    }

    #[Then('there are :arg1 buttons')]
    public function thereAreButtons($arg1): void
    {
        if ($this->matchingSubnavLinks($arg1) === []) {
            throw new \RuntimeException(sprintf('Expected a "%s" pagination link.', $arg1));
        }
    }

    #[Then(':arg1 page :arg3 with content :arg2 appears')]
    public function pageWithContentAppears($arg1, $arg2, $arg3): void
    {
        $expectedPath = sprintf('/titleslist/%d/', (int) $arg3);
        $currentPath = parse_url($this->getSession()->getCurrentUrl(), PHP_URL_PATH);
        if (!is_string($currentPath) || !str_ends_with($currentPath, $expectedPath)) {
            throw new \RuntimeException(sprintf('Expected the Books page URL to end in "%s".', $expectedPath));
        }
        $this->theMenuItemIsActive($arg1);
        $this->assertSession()->pageTextContains($arg2);
    }

    #[When('I go to the :arg1 page :arg2')]
    public function iGoToThePage($arg1, $arg2): void
    {
        if ($arg1 !== 'Books') {
            throw new \InvalidArgumentException(sprintf('Direct pagination is not configured for "%s".', $arg1));
        }
        $this->visitPath(sprintf('/titleslist/%d/', (int) $arg2));
    }

    #[When('I enter :arg1 into the :arg2 search field')]
    public function iEnterIntoTheSearchField($arg1, $arg2): void
    {
        throw new PendingException();
    }

    #[Then('the search result page appears')]
    public function theSearchResultPageAppears(): void
    {
        throw new PendingException();
    }

    #[Then('the page contains :arg1')]
    public function thePageContains($arg1): void
    {
        throw new PendingException();
    }

    #[When('I navigate to the home page')]
    public function iNavigateToTheHomePage(): void
    {
        $this->visitPath('/');
    }

    #[When('search for :arg1 in :arg2')]
    public function searchForIn($arg1, $arg2): void
    {
        throw new PendingException();
    }

    #[Then('I see :arg1 book as a result')]
    public function iSeeBookAsAResult($arg1): void
    {
        throw new PendingException();
    }

    #[Then('I see :arg1 authors as a result')]
    public function iSeeAuthorsAsAResult($arg1): void
    {
        throw new PendingException();
    }

    #[Then('I see :arg1 tags as a result')]
    public function iSeeTagsAsAResult($arg1): void
    {
        throw new PendingException();
    }

    #[Then('I see :arg1 series as a result')]
    public function iSeeSeriesAsAResult($arg1): void
    {
        throw new PendingException();
    }

    #[Then('I see :arg1 books as a result')]
    public function iSeeBooksAsAResult($arg1): void
    {
        throw new PendingException();
    }

    #[Given('the application is configured with a page size of :arg1')]
    public function theApplicationIsConfiguredWithAPageSizeOf($arg1): void
    {
        $dataPath = getenv('BBS_DATA_DB');
        if ($dataPath === false || $dataPath === '') {
            throw new \RuntimeException('BBS_DATA_DB must point to an isolated database for Behat.');
        }

        $this->visitPath('/login/');
        $page = $this->getSession()->getPage();
        $page->fillField('username', 'admin');
        $page->fillField('password', 'admin');
        $page->pressButton('login');

        $this->visitPath('/admin/configuration/');
        $page = $this->getSession()->getPage();
        $page->fillField('calibre_dir', dirname(__DIR__, 2) . '/tests/fixtures/lib2');
        $page->fillField('page_size', (string) $arg1);
        $page->pressButton('Save');
    }

    #[Then('I see my :arg1 newest books')]
    public function iSeeMyNewestBooks($arg1): void
    {
        $count = (int) $arg1;
        $this->assertSession()->elementsCount('css', '.bbs-list-item', $count);
        foreach (array_slice(['The Stones of Venice, Volume II', 'Neues Leben'], 0, $count) as $title) {
            $this->assertSession()->pageTextContains($title);
        }
    }

    private function matchingSubnavLinks(string $label): array
    {
        $needle = mb_strtolower(trim($label));
        return array_values(array_filter(
            $this->getSession()->getPage()->findAll('css', 'a.bbs-subnav-link'),
            static fn($link): bool => str_contains(mb_strtolower($link->getText()), $needle)
        ));
    }

    #[When('I navigate to title details page :arg1')]
    public function iNavigateToTitleDetailsPage($arg1): void
    {
        throw new PendingException();
    }

    #[Then('there are :arg1 id links')]
    public function thereAreIdLinks($arg1): void
    {
        throw new PendingException();
    }

    #[Then('the id links contain label :arg1 and url :arg2')]
    public function theIdLinksContainLabelAndUrl($arg1, $arg2): void
    {
        throw new PendingException();
    }

    #[Given('I am logged in as :arg1')]
    public function iAmLoggedInAs($arg1): void
    {
        throw new PendingException();
    }

    #[When('I open the logout page')]
    public function iOpenTheLogoutPage(): void
    {
        throw new PendingException();
    }

    #[Then('the logout page is displayed')]
    public function theLogoutPageIsDisplayed(): void
    {
        throw new PendingException();
    }

    #[Then('I am no longer authenticated')]
    public function iAmNoLongerAuthenticated(): void
    {
        throw new PendingException();
    }

    #[Then('there are no :arg1 options')]
    public function thereAreNoOptions($arg1): void
    {
        throw new PendingException();
    }

    #[When('I open the :arg1 form')]
    public function iOpenTheForm($arg1): void
    {
        throw new PendingException();
    }

    #[When('I enter the recipient email :arg1')]
    public function iEnterTheRecipientEmail($arg1): void
    {
        throw new PendingException();
    }

    #[When('I submit the form')]
    public function iSubmitTheForm(): void
    {
        throw new PendingException();
    }

    #[Then('I get the message :arg1')]
    public function iGetTheMessage($arg1): void
    {
        throw new PendingException();
    }

    #[When('I request series notes for series :arg1')]
    public function iRequestSeriesNotesForSeries($arg1): void
    {
        throw new PendingException();
    }

    #[Then('the series note is empty')]
    public function theSeriesNoteIsEmpty(): void
    {
        throw new PendingException();
    }

    #[When('I save the Markdown note :arg1 for series :arg2')]
    public function iSaveTheMarkdownNoteForSeries($arg1, $arg2): void
    {
        throw new PendingException();
    }

    #[Then('the response contains the rendered heading :arg1')]
    public function theResponseContainsTheRenderedHeading($arg1): void
    {
        throw new PendingException();
    }

    #[Then('the response message is :arg1')]
    public function theResponseMessageIs($arg1): void
    {
        throw new PendingException();
    }

    #[When('I delete the note for series :arg1')]
    public function iDeleteTheNoteForSeries($arg1): void
    {
        throw new PendingException();
    }

    #[When('I request links for series :arg1')]
    public function iRequestLinksForSeries($arg1): void
    {
        throw new PendingException();
    }

    #[Then('the series link list is empty')]
    public function theSeriesLinkListIsEmpty(): void
    {
        throw new PendingException();
    }

    #[When('I add the link :arg1 with label :arg2 to series :arg3')]
    public function iAddTheLinkWithLabelToSeries($arg1, $arg2, $arg3): void
    {
        throw new PendingException();
    }

    #[Then('the response contains the new link identifier')]
    public function theResponseContainsTheNewLinkIdentifier(): void
    {
        throw new PendingException();
    }

    #[When('I delete that link from series :arg1')]
    public function iDeleteThatLinkFromSeries($arg1): void
    {
        throw new PendingException();
    }

    #[Then('clicking on :arg1 reveals the download options')]
    public function clickingOnRevealsTheDownloadOptions($arg1): void
    {
        throw new PendingException();
    }

    #[Then('clicking on download format :arg1 starts the download for file :arg2 and length :arg3')]
    public function clickingOnDownloadFormatStartsTheDownloadForFileAndLength($arg1, $arg2, $arg3): void
    {
        throw new PendingException();
    }

    #[When('I click on author :arg1')]
    public function iClickOnAuthor($arg1): void
    {
        throw new PendingException();
    }

    #[When('I click on :arg1 to reveal the tags')]
    public function iClickOnToRevealTheTags($arg1): void
    {
        throw new PendingException();
    }

    #[When('I click on tag :arg1')]
    public function iClickOnTag($arg1): void
    {
        throw new PendingException();
    }

    #[Then('there are no :arg1 links')]
    public function thereAreNoLinks($arg1): void
    {
        throw new PendingException();
    }

    #[Then('there are :arg1 links')]
    public function thereAreLinks($arg1): void
    {
        throw new PendingException();
    }

    #[Then('I click on :arg1 to reveal the series')]
    public function iClickOnToRevealTheSeries($arg1): void
    {
        throw new PendingException();
    }

    #[Then('the series link contains the text :arg1')]
    public function theSeriesLinkContainsTheText($arg1): void
    {
        throw new PendingException();
    }

    #[Then('I click on series :arg1')]
    public function iClickOnSeries($arg1): void
    {
        throw new PendingException();
    }

    #[Then('there is no custom column info')]
    public function thereIsNoCustomColumnInfo(): void
    {
        throw new PendingException();
    }

    #[Then('there is custom column info')]
    public function thereIsCustomColumnInfo(): void
    {
        throw new PendingException();
    }

    #[Then('I click on :arg1 to reveal the custom column info :arg2')]
    public function iClickOnToRevealTheCustomColumnInfo($arg1, $arg2): void
    {
        throw new PendingException();
    }

    #[When('I select the date sorting option')]
    public function iSelectTheDateSortingOption(): void
    {
        throw new PendingException();
    }

    #[Then('the titles are ordered by the configured date field, newest first')]
    public function theTitlesAreOrderedByTheConfiguredDateFieldNewestFirst(): void
    {
        throw new PendingException();
    }

    #[When('I select the title sorting option')]
    public function iSelectTheTitleSortingOption(): void
    {
        throw new PendingException();
    }

    #[Then('the titles are ordered alphabetically by title')]
    public function theTitlesAreOrderedAlphabeticallyByTitle(): void
    {
        throw new PendingException();
    }
}
