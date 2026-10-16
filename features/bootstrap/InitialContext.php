<?php

use Behat\Step\Then;
use Behat\Step\When;
use Behat\MinkExtension\Context\RawMinkContext;

/**
 * Defines application features from the specific context.
 */
class InitialContext extends RawMinkContext
{
    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct() {}

    #[Then('I get the login page')]
    public function iGetTheLoginPage(): void
    {
        $this->assertSession()->statusCodeEquals(200);
        $this->assertSession()->elementExists('css', '#loginform');
    }

    #[When('I login as user :arg1')]
    public function iLoginAsUser($arg1): void
    {
        $this->requireIsolatedDatabase();
        $this->visitPath('/login/');
        $page = $this->getSession()->getPage();
        $page->fillField('username', $arg1);
        $page->fillField('password', $arg1 === 'admin' ? 'admin' : 'invalid');
        $page->pressButton('login');
    }

    #[Then('I get the installation page')]
    public function iGetTheInstallationPage(): void
    {
        $this->assertAdminConfigurationPage();
    }

    #[When('I visit :arg1')]
    public function iVisitPath($arg1): void
    {
        $this->visitPath($arg1);
    }

    #[Then('I get redirected to the admin page')]
    public function iGetRedirectedToTheAdminPage(): void
    {
        $this->assertAdminConfigurationPage();
    }

    #[When('I enter an invalid directory name')]
    public function iEnterAnInvalidDirectoryName(): void
    {
        $this->requireIsolatedDatabase();
        $this->visitPath('/admin/configuration/');
        $page = $this->getSession()->getPage();
        $page->fillField('calibre_dir', sys_get_temp_dir() . '/bbs-invalid-calibre-' . bin2hex(random_bytes(6)));
        $page->pressButton('Save');
    }

    #[Then('I get an error message')]
    public function iGetAnErrorMessage(): void
    {
        $this->assertSession()->pageTextContains('The configured Calibre directory cannot be used.');
    }

    #[When('I enter a valid directory name')]
    public function iEnterAValidDirectoryName(): void
    {
        $this->requireIsolatedDatabase();
        $this->visitPath('/admin/configuration/');
        $page = $this->getSession()->getPage();
        $page->fillField('calibre_dir', dirname(__DIR__, 2) . '/tests/fixtures/lib2');
        $page->pressButton('Save');
    }

    #[Then('the application saves the configuration')]
    public function theApplicationSavesTheConfiguration(): void
    {
        $this->assertSession()->pageTextContains('Changes saved');
        $field = $this->getSession()->getPage()->findField('calibre_dir');
        $expectedPath = dirname(__DIR__, 2) . '/tests/fixtures/lib2';
        if ($field === null || $field->getValue() !== $expectedPath) {
            throw new \RuntimeException('The configured Calibre directory was not retained.');
        }
    }

    #[Then('I can navigate to the home page')]
    public function iCanNavigateToTheHomePage(): void
    {
        $this->visitPath('/');
        $this->assertSession()->statusCodeEquals(200);
        $this->assertSession()->pageTextContains('Most recent');
    }

    private function assertAdminConfigurationPage(): void
    {
        $this->assertSession()->statusCodeEquals(200);
        $this->assertSession()->elementExists('css', '#adminform');
        if (!preg_match('~/admin/configuration/?(?:\?.*)?$~', $this->getSession()->getCurrentUrl())) {
            throw new \RuntimeException('Expected to be redirected to the admin configuration page.');
        }
    }

    private function requireIsolatedDatabase(): void
    {
        $dataPath = getenv('BBS_DATA_DB');
        if ($dataPath === false || $dataPath === '') {
            throw new \RuntimeException('BBS_DATA_DB must point to an isolated database for Behat.');
        }
    }
}
