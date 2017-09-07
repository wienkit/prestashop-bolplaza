<?php
namespace Wienkit\Prestashop\Bolplaza;

/**
 * Class SetupModuleTest
 *
 * @package Wienkit\Prestashop\Bolplaza
 */
class SetupModuleTest extends BaseTest
{

    public function testAdminLogin()
    {
        $this->doAdminLogin();
        $title = $this->driver->findElement(\WebDriverBy::tagName('h2'))->getText();
        $this->assertEquals('Dashboard', $title);
    }

    /**
     * @group 16
     */
    public function testEnableModule()
    {
        $this->doAdminLogin();
        $this->goToPath('index.php?controller=AdminModules');
        $this->goToPath('index.php?controller=AdminModules&install=bolplaza&tab_module=market_place&module_name=bolplaza');
        $text = $this->driver->findElement(\WebDriverBy::cssSelector(".bootstrap .alert-success"))->getText();
        $this->assertContains("Installatie module(s) geslaagd", $text);
    }

    /**
     * @group 16
     * @depends testEnableModule
     */
    public function testConfigureModule()
    {
        $this->doAdminLogin();
        $this->goToPath('index.php?controller=AdminModules&configure=bolplaza&tab_module=market_place&module_name=bolplaza');
        $this->driver->findElement(\WebDriverBy::id('bolplaza_orders_testmode_on'))->click();
    }
}