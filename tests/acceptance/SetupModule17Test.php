<?php
namespace Wienkit\Prestashop\Bolplaza;

use Wienkit\Prestashop\Bolplaza\Base\AbstractAdmin17TestBase;

/**
 * Class SetupModuleTest
 *
 * @group 17
 * @package Wienkit\Prestashop\Bolplaza
 */
class SetupModule17Test extends AbstractAdmin17TestBase
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
        $this->goToMenu(['Modules', 'Modules & Services']);
        $this->driver->wait()->until(
            \WebDriverExpectedCondition::visibilityOfElementLocated(\WebDriverBy::className('module-search-result-wording'))
        );
        $this->driver->findElement(\WebDriverBy::className('module-tags-input'))->sendKeys('bol.com');
        $this->driver->findElement(\WebDriverBy::className('search-button'))->click();
        $this->driver->wait()->until(
            \WebDriverExpectedCondition::elementToBeClickable(\WebDriverBy::cssSelector('button[data-confirm_modal="module-modal-confirm-bolplaza-install"]'))
        );
        $this->driver->findElement(\WebDriverBy::cssSelector('button[data-confirm_modal="module-modal-confirm-bolplaza-install"]'))->click();
        $this->driver->wait()->until(
            \WebDriverExpectedCondition::elementToBeClickable(\WebDriverBy::cssSelector('button[data-confirm_modal="module-modal-confirm-bolplaza-configure"]'))
        );
        $text = $this->driver->findElement(\WebDriverBy::cssSelector('button[data-confirm_modal="module-modal-confirm-bolplaza-configure"]'))->getText();
        $this->assertContains("CONFIGUREER", $text);
    }

    /**
     * @depends testEnableModule
     */
    public function testConfigureModule()
    {
        $this->doAdminLogin();
        $this->goToMenu(['Modules', 'Modules & Services']);
        $this->driver->findElement(\WebDriverBy::linkText("Geïnstalleerde modules"))->click();
        $this->driver->findElement(\WebDriverBy::cssSelector('button[data-confirm_modal="module-modal-confirm-bolplaza-configure"]'))->click();

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