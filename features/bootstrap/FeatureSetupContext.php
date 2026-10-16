<?php

use Behat\Behat\Context\Context;
use Behat\Hook\BeforeScenario;
use BicBucStriim\AppData\Settings;

class FeatureSetupContext implements Context
{
    #[BeforeScenario]
    public function configureFixtureLibrary(): void
    {
        $dataPath = getenv('BBS_DATA_DB');
        if ($dataPath === false || $dataPath === '' || !is_file($dataPath) || !is_writable($dataPath)) {
            throw new \RuntimeException('BBS_DATA_DB must point to a writable, isolated database for Behat.');
        }

        $libraryPath = dirname(__DIR__, 2) . '/tests/fixtures/lib2';
        if (!is_file($libraryPath . '/metadata.db')) {
            throw new \RuntimeException(sprintf('The Behat Calibre fixture is missing: %s', $libraryPath));
        }

        $database = new PDO('sqlite:' . $dataPath);
        $database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $statement = $database->prepare(
            'INSERT INTO config (name, val) VALUES (:name, :value) '
            . 'ON CONFLICT(name) DO UPDATE SET val = excluded.val'
        );
        $statement->execute([
            ':name' => Settings::CALIBRE_DIR,
            ':value' => $libraryPath,
        ]);
        $statement->execute([
            ':name' => Settings::PAGE_SIZE,
            ':value' => '2',
        ]);
        $statement->execute([
            ':name' => Settings::LOGIN_REQUIRED,
            ':value' => '0',
        ]);
    }
}
