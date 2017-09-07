<?php
namespace Wienkit\Prestashop\Bolplaza;

use PHPUnit\Framework\TestCase;

abstract class BaseTest extends TestCase
{
    /** @var \RemoteWebDriver */
    protected $driver;

    /** @var string */
    private $token;

    public function setUp()
    {
        $host = 'http://selenium-standalone-firefox:4444/wd/hub';
        $this->driver = \RemoteWebDriver::create($host, \DesiredCapabilities::chrome());
    }

    public function goToPath($path)
    {
        $this->driver->get('http://localhost/admin-dev/' . $path);
        try {
            $this->driver->findElement(\WebDriverBy::linkText("Ik begrijp het risico maar wil de pagina toch bekijken"))->click();
        } catch (\NoSuchElementException $e) {
        }
    }

    public function doAdminLogin()
    {
        $this->goToPath('index.php', false);
        $this->driver->findElement(\WebDriverBy::name('email'))->click();
        $this->driver->getKeyboard()->sendKeys('admin@example.com');
        $this->driver->findElement(\WebDriverBy::name('passwd'))->click();
        $this->driver->getKeyboard()->sendKeys('password');
        $this->driver->findElement(\WebDriverBy::name('submitLogin'))->click();
        $this->driver->wait()->until(
            \WebDriverExpectedCondition::titleContains('Dashboard')
        );
        $url = $this->driver->getCurrentURL();
        $parts = parse_url($url);
        parse_str($parts['query'], $query);
        $this->token = $query['token'];
    }

    public function tearDown()
    {
        $this->driver->close();
    }
}