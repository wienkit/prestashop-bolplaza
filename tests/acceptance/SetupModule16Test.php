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
        $this->driver->findElement(\WebDriverBy::cssSelector("label[for='bolplaza_orders_enabled_on']"))->click();
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

    public function testSetProductPrice()
    {
        $this->doAdminLogin();
        $this->goToPath('index.php?controller=AdminProducts&id_product=1&updateproduct');
        sleep(10);
        $this->driver->findElement(\WebDriverBy::name('ean13'))->clear()->sendKeys('9789062387410');
        $button = $this->driver->findElement(\WebDriverBy::cssSelector('#product-tab-content-Informations [name="submitAddproductAndStay"]'));
        $button->getLocationOnScreenOnceScrolledIntoView();
        $button->click();
        $this->assertContains('Succesvolle wijziging', $this->getStatusMessageText());
        $this->driver->findElement(\WebDriverBy::id('link-ModuleBolplaza'))->click();
        sleep(2);
        $this->driver->findElement(\WebDriverBy::id('toggle_bolplaza_check'))->click();
        $button = $this->driver->findElement(\WebDriverBy::cssSelector('#product-tab-content-ModuleBolplaza [name="submitAddproduct"]'));
        $button->getLocationOnScreenOnceScrolledIntoView();
        $button->click();
        $this->assertContains('Succesvolle wijziging', $this->getStatusMessageText());
    }


    /**
     * @depends testConfigureModule
     */
    public function testSyncOrders()
    {
        $this->doAdminLogin();
        $this->goToPath('index.php?controller=AdminBolPlazaOrders');
        $this->driver->findElement(\WebDriverBy::id('page-header-desc-bolplaza_item-sync_orders'))->click();
        $this->assertContains('Bol.com order sync completed', $this->getStatusMessageText());
        $tableText = $this->driver->findElement(\WebDriverBy::className('bolplaza_item'))->getText();
        $this->assertContains('Harry Potter', $tableText);
        $this->assertContains('123', $tableText);
        $this->assertContains('321', $tableText);
    }

}