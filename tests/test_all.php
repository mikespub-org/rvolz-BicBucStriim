<?php

if (!defined('REDBEAN_MODEL_PREFIX')) {
    define('REDBEAN_MODEL_PREFIX', '\\BicBucStriim\\AppData\\Model_');
}
set_include_path("tests:vendor");
require 'autoload.php';
require 'simpletest/simpletest/autorun.php';

class TestsAll extends TestSuite
{
    public function __construct(string $label = 'All Tests')
    {
        parent::__construct($label);
        $this->addFile('BicBucStriimTest.php');
        $this->addFile('CalibreTest.php');
        $this->addFile('CalibreFilterTest.php');
        $this->addFile('CalibreIcuTest.php');
        $this->addFile('CustomColumnsTest.php');
        $this->addFile('L10nTest.php');
        $this->addFile('UtilitiesTest.php');
        // TODO reenable OPDS tests
        //$this->addFile('OpdsGeneratorTest.php');
        $this->addFile('InstUtilsTest.php');
    }
}
