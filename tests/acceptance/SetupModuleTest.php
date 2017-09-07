<?php
namespace Wienkit\Prestashop\Bolplaza;


class SetupModuleTest extends \PHPUnit_Extensions_Selenium2TestCase
{
    public function setUp()
    {
        $this->setHost('selenium-standalone-firefox');
        $this->setPort(4444);
        $this->setBrowserUrl('http://localhost');
        $this->setBrowser('firefox');
    }

    public function testTheThing()
    {
        $this->assertTrue(true);
    }

    public function tearDown()
    {
        $this->stop();
    }
}