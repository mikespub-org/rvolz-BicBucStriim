<?php

use Behat\Step\Then;
use Behat\Step\When;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;

/**
 * Defines application features from the specific context.
 * @suppress PHP0413
 */
class AdminContext implements Context
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

    #[When('I add an id template with label :arg1 and URL :arg2 for id :arg3')]
    public function iAddAnIdTemplateWithLabelAndUrlForId($arg1, $arg2, $arg3): void
    {
        throw new PendingException();
    }

    #[Then('I get a success message :arg1')]
    public function iGetASuccessMessage($arg1): void
    {
        throw new PendingException();
    }

    #[Then('the id template :arg1 contains label :arg2 and URL :arg3')]
    public function theIdTemplateContainsLabelAndUrl($arg1, $arg2, $arg3): void
    {
        throw new PendingException();
    }

    #[Then('I get the error message :arg1')]
    public function iGetTheErrorMessage($arg1): void
    {
        throw new PendingException();
    }

    #[When('I enter the user credentials :arg1, :arg2')]
    public function iEnterTheUserCredentials($arg1, $arg2): void
    {
        throw new PendingException();
    }

    #[Then('I get the success message :arg1')]
    public function iGetTheSuccessMessage($arg1): void
    {
        throw new PendingException();
    }

    #[Then('the list contains :arg1 items')]
    public function theListContainsItems($arg1): void
    {
        throw new PendingException();
    }

    #[Then('the list contains user :arg1')]
    public function theListContainsUser($arg1): void
    {
        throw new PendingException();
    }

    #[When('I set the :arg1 slider to :arg2')]
    public function iSetTheSliderTo($arg1, $arg2): void
    {
        throw new PendingException();
    }

    #[When('I save the configuration')]
    public function iSaveTheConfiguration(): void
    {
        throw new PendingException();
    }

    #[Then('the :arg1 slider is set to :arg2')]
    public function theSliderIsSetTo($arg1, $arg2): void
    {
        throw new PendingException();
    }

    #[When('I reload the configuration page')]
    public function iReloadTheConfigurationPage(): void
    {
        throw new PendingException();
    }

    #[When('I set the title date sort to :arg1')]
    public function iSetTheTitleDateSortTo($arg1): void
    {
        throw new PendingException();
    }

    #[When('I set the page size to :arg1')]
    public function iSetThePageSizeTo($arg1): void
    {
        throw new PendingException();
    }

    #[Then('the title date sort is :arg1')]
    public function theTitleDateSortIs($arg1): void
    {
        throw new PendingException();
    }

    #[Then('the page size is :arg1')]
    public function thePageSizeIs($arg1): void
    {
        throw new PendingException();
    }

    #[Then('the configuration is not saved')]
    public function theConfigurationIsNotSaved(): void
    {
        throw new PendingException();
    }

    #[Then('I see a page-size validation error')]
    public function iSeeAPageSizeValidationError(): void
    {
        throw new PendingException();
    }

    #[When('I enable Send-to-Kindle')]
    public function iEnableSendToKindle(): void
    {
        throw new PendingException();
    }

    #[When('I clear the Kindle sender email')]
    public function iClearTheKindleSenderEmail(): void
    {
        throw new PendingException();
    }

    #[Then('I see a sender-email validation error')]
    public function iSeeASenderEmailValidationError(): void
    {
        throw new PendingException();
    }

    #[When('I enter the id template data :arg1, :arg2 for id :arg3')]
    public function iEnterTheIdTemplateDataForId($arg1, $arg2, $arg3): void
    {
        throw new PendingException();
    }

    #[When('I delete id template :arg1')]
    public function iDeleteIdTemplate($arg1): void
    {
        throw new PendingException();
    }

    #[When('I add a user account with name :arg1 and password :arg2')]
    public function iAddAUserAccountWithNameAndPassword($arg1, $arg2): void
    {
        throw new PendingException();
    }

    #[Then('the user list contains :arg1')]
    public function theUserListContains($arg1): void
    {
        throw new PendingException();
    }

    #[When('I delete user :arg1')]
    public function iDeleteUser($arg1): void
    {
        throw new PendingException();
    }

    #[When('I confirm the deletion')]
    public function iConfirmTheDeletion(): void
    {
        throw new PendingException();
    }

    #[Then('the user list doesn\'t contain :arg1')]
    public function theUserListDoesntContain($arg1): void
    {
        throw new PendingException();
    }

    #[When('I edit user :arg1 and change the password to :arg2')]
    public function iEditUserAndChangeThePasswordTo($arg1, $arg2): void
    {
        throw new PendingException();
    }

    #[When('I edit user :arg1 and change the language filter to :arg2')]
    public function iEditUserAndChangeTheLanguageFilterTo($arg1, $arg2): void
    {
        throw new PendingException();
    }

    #[When('I edit user :arg1 and change the tag filter to :arg2')]
    public function iEditUserAndChangeTheTagFilterTo($arg1, $arg2): void
    {
        throw new PendingException();
    }

    #[When('I edit user :arg1 and change the role to :arg2')]
    public function iEditUserAndChangeTheRoleTo($arg1, $arg2): void
    {
        throw new PendingException();
    }

    #[Then('the user :arg1 has the :arg2 role')]
    public function theUserHasTheRole($arg1, $arg2): void
    {
        throw new PendingException();
    }

    #[Then('the list contains :arg1 item')]
    public function theListContainsItem($arg1): void
    {
        throw new PendingException();
    }

    #[When('I enter SMTP username :arg1')]
    public function iEnterSmtpUsername($arg1): void
    {
        throw new PendingException();
    }

    #[When('I enter SMTP password :arg1')]
    public function iEnterSmtpPassword($arg1): void
    {
        throw new PendingException();
    }

    #[When('I enter SMTP server :arg1')]
    public function iEnterSmtpServer($arg1): void
    {
        throw new PendingException();
    }

    #[When('I enter SMTP port :arg1')]
    public function iEnterSmtpPort($arg1): void
    {
        throw new PendingException();
    }

    #[When('I select SMTP encryption :arg1')]
    public function iSelectSmtpEncryption($arg1): void
    {
        throw new PendingException();
    }

    #[When('I save the mail configuration')]
    public function iSaveTheMailConfiguration(): void
    {
        throw new PendingException();
    }

    #[Then('the mail configuration shows server :arg1')]
    public function theMailConfigurationShowsServer($arg1): void
    {
        throw new PendingException();
    }

    #[Then('it shows port :arg2 and encryption :arg1')]
    public function itShowsPortAndEncryption($arg1, $arg2): void
    {
        throw new PendingException();
    }

    #[When('I reload the mail configuration page')]
    public function iReloadTheMailConfigurationPage(): void
    {
        throw new PendingException();
    }

    #[Then('the mail configuration still shows server :arg1')]
    public function theMailConfigurationStillShowsServer($arg1): void
    {
        throw new PendingException();
    }

    #[Then('it still shows port :arg2 and encryption :arg1')]
    public function itStillShowsPortAndEncryption($arg1, $arg2): void
    {
        throw new PendingException();
    }

    #[When('I open the version check page')]
    public function iOpenTheVersionCheckPage(): void
    {
        throw new PendingException();
    }

    #[Then('the page displays the current version and a check result')]
    public function thePageDisplaysTheCurrentVersionAndACheckResult(): void
    {
        throw new PendingException();
    }
}
