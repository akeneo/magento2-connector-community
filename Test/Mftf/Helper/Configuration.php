<?php

declare(strict_types=1);

namespace Akeneo\Connector\Test\Mftf\Helper;

use Exception;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Magento\FunctionalTestingFramework\Helper\Helper;
use Magento\FunctionalTestingFramework\Module\MagentoWebDriver;

class Configuration extends Helper
{
    public function openApiConfigurationTab(): void
    {
        /** @var MagentoWebDriver $webDriver */
        $magentoWebDriver = $this->getModule('\Magento\FunctionalTestingFramework\Module\MagentoWebDriver');

        /** @var RemoteWebDriver $webDriver */
        $webDriver = $magentoWebDriver->webDriver;

        // Hack for not clicking tab if already opened in user extra data
        try {
            $webDriver->findElement(WebDriverBy::cssSelector('#akeneo_connector_akeneo_api-head.open'));
        } catch (Exception) {
            $webDriver->findElement(WebDriverBy::id('akeneo_connector_akeneo_api-head'))->click();
        }
    }
}
