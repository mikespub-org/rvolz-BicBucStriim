<?php

use Behat\Step\Then;
use Behat\Step\When;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;

/**
 * Defines application features from the specific context.
 */
class AuthorContext implements Context
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
        throw new PendingException();
    }

    #[When('the app switches to the :arg1 view')]
    public function theAppSwitchesToTheView($arg1): void
    {
        throw new PendingException();
    }

    #[Then('I get a success message :arg1')]
    public function iGetASuccessMessage($arg1): void
    {
        throw new PendingException();
    }

    #[Then('I get the error message :arg1')]
    public function iGetTheErrorMessage($arg1): void
    {
        throw new PendingException();
    }

    #[Then('I get the success message :arg1')]
    public function iGetTheSuccessMessage($arg1): void
    {
        throw new PendingException();
    }

    #[When('I confirm the deletion')]
    public function iConfirmTheDeletion(): void
    {
        throw new PendingException();
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

    #[When('I click on the metadata menu')]
    public function iClickOnTheMetadataMenu(): void
    {
        throw new PendingException();
    }

    #[When('I click on the image menu button')]
    public function iClickOnTheImageMenuButton(): void
    {
        throw new PendingException();
    }

    #[Then('the author image panel appears')]
    public function theAuthorImagePanelAppears(): void
    {
        throw new PendingException();
    }

    #[When('I attach the author image file :arg1')]
    public function iAttachTheAuthorImageFile($arg1): void
    {
        throw new PendingException();
    }

    #[When('press the :arg1 button')]
    public function pressTheButton($arg1): void
    {
        throw new PendingException();
    }

    #[Then('there is an author image')]
    public function thereIsAnAuthorImage(): void
    {
        throw new PendingException();
    }

    #[Then('there is no author image')]
    public function thereIsNoAuthorImage(): void
    {
        throw new PendingException();
    }

    #[Then('the entry for author :arg1 shows the custom thumbnail :arg2')]
    public function theEntryForAuthorShowsTheCustomThumbnail($arg1, $arg2): void
    {
        throw new PendingException();
    }

    #[When('press the :arg1 link')]
    public function pressTheLink($arg1): void
    {
        throw new PendingException();
    }

    #[When('I close the author image panel')]
    public function iCloseTheAuthorImagePanel(): void
    {
        throw new PendingException();
    }

    #[Then('the entry for author :arg1 shows the default thumbnail')]
    public function theEntryForAuthorShowsTheDefaultThumbnail($arg1): void
    {
        throw new PendingException();
    }

    #[When('I press the :arg1 button')]
    public function iPressTheButton($arg1): void
    {
        throw new PendingException();
    }

    #[Then('the displayed author image has been replaced with the new upload')]
    public function theDisplayedAuthorImageHasBeenReplacedWithTheNewUpload(): void
    {
        throw new PendingException();
    }

    #[When('the app switches to the :arg1 page :arg3 for :arg2')]
    public function theAppSwitchesToThePageFor($arg1, $arg2, $arg3): void
    {
        throw new PendingException();
    }

    #[When('I click on the :arg1 button')]
    public function iClickOnTheButton($arg1): void
    {
        throw new PendingException();
    }

    #[When('I add an author link with label :arg1 and URL :arg2')]
    public function iAddAnAuthorLinkWithLabelAndUrl($arg1, $arg2): void
    {
        throw new PendingException();
    }

    #[Then('the author links list contains the link :arg1 with label :arg2')]
    public function theAuthorLinksListContainsTheLinkWithLabel($arg1, $arg2): void
    {
        throw new PendingException();
    }

    #[When('I delete the author link :arg1')]
    public function iDeleteTheAuthorLink($arg1): void
    {
        throw new PendingException();
    }

    #[Then('the author links list doesn\'t contain the link :arg1')]
    public function theAuthorLinksListDoesntContainTheLink($arg1): void
    {
        throw new PendingException();
    }

    #[When('I request the notes for author :arg1')]
    public function iRequestTheNotesForAuthor($arg1): void
    {
        throw new PendingException();
    }

    #[When('I add the author note :arg1')]
    public function iAddTheAuthorNote($arg1): void
    {
        throw new PendingException();
    }

    #[Then('the author note response contains the rendered note')]
    public function theAuthorNoteResponseContainsTheRenderedNote(): void
    {
        throw new PendingException();
    }

    #[When('I delete the author note')]
    public function iDeleteTheAuthorNote(): void
    {
        throw new PendingException();
    }

    #[Then('the author has no note')]
    public function theAuthorHasNoNote(): void
    {
        throw new PendingException();
    }

    #[Then('just name :arg1 is mentioned')]
    public function justNameIsMentioned($arg1): void
    {
        throw new PendingException();
    }

    #[Then('there are no author links')]
    public function thereAreNoAuthorLinks(): void
    {
        throw new PendingException();
    }

    #[Then('there are two books')]
    public function thereAreTwoBooks(): void
    {
        throw new PendingException();
    }
}
