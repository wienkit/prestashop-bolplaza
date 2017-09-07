<?php
namespace Wienkit\Prestashop\Bolplaza;


class SetupModuleTest extends \PHPUnit_Extensions_Selenium2TestCase
{
    public function setUp()
    {
        $this->setHost('selenium');
        $this->setPort(4444);
        $this->setBrowserUrl('http://localhost');
        $this->setBrowser('chrome');
    }

    public function tearDown()
    {
        $this->stop();
    }
}