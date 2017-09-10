<?php
namespace Wienkit\Prestashop\Bolplaza;

use Wienkit\Prestashop\Bolplaza\Base\ATestBase;

/**
 * Class SetupModuleTest
 *
 * @group 16
 * @package Wienkit\Prestashop\Bolplaza
 */
class SetupModule16Test extends ATestBase
{

    public function testAdminLogin()
    {
        $this->doAdminLogin();
        $title = $this->driver->findElement(\WebDriverBy::tagName('h2'))->getText();
        $this->assertEquals('Dashboard', $title);
    }

    public function testEnableModule()
    {
        $this->doAdminLogin();
        $this->goToPath('index.php?controller=AdminModules');
        $this->goToPath('index.php?controller=AdminModules&install=bolplaza&tab_module=market_place&module_name=bolplaza');
        $this->assertContains("Installatie module(s) geslaagd", $this->getStatusMessageText());
    }

    /**
     * @depends testEnableModule
     */
    public function testConfigureModule()
    {
        $this->doAdminLogin();
        $this->goToPath('index.php?controller=AdminModules&configure=bolplaza&tab_module=market_place&module_name=bolplaza');
        $this->driver->findElement(\WebDriverBy::cssSelector("label[for='bolplaza_orders_testmode_on']"))->click();
        $this->driver->findElement(\WebDriverBy::id('bolplaza_orders_pubkey'))->sendKeys(getenv('PUBLIC_KEY'));
        $this->driver->findElement(\WebDriverBy::id('bolplaza_orders_privkey'))->sendKeys(getenv('PRIVATE_KEY'));
        $this->driver->findElement(\WebDriverBy::id('bolplaza_orders_carrier_code'))->sendKeys('BRIEFPOST');
        $this->driver->findElement(\WebDriverBy::id('bolplaza_price_addition'))->sendKeys('5');
        $this->driver->findElement(\WebDriverBy::id('bolplaza_price_multiplication'))->sendKeys('1.15');
        $this->driver->findElement(\WebDriverBy::id('bolplaza_price_roundup'))->sendKeys('0.1');
        $this->driver->findElement(\WebDriverBy::id('configuration_form'))->submit();
        $this->assertContains("Instellingen opgeslagen", $this->getStatusMessageText());
    }
}