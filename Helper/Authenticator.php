<?php

declare(strict_types=1);

namespace Akeneo\Connector\Helper;

use Akeneo\Connector\Model\AkeneoPimClientBuilderFactory;
use Akeneo\Connector\Helper\Config as ConfigHelper;
use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Http\Adapter\Guzzle6\Client;
use Http\Factory\Guzzle\StreamFactory;
use Http\Factory\Guzzle\RequestFactory;

/**
 * Class Authenticator
 *
 * @category  Class
 * @package   Akeneo\Connector\Helper
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Authenticator
{
    /** @var ConfigHelper $configHelper */
    private $configHelper;

    /** @var AkeneoPimClientBuilderFactory $clientBuilderFactory */
    private $clientBuilderFactory;

    /**
     * @param Config $configHelper
     * @param AkeneoPimClientBuilderFactory $clientBuilderFactory
     */
    public function __construct(ConfigHelper $configHelper, AkeneoPimClientBuilderFactory $clientBuilderFactory)
    {
        $this->configHelper = $configHelper;
        $this->clientBuilderFactory = $clientBuilderFactory;
    }

    /**
     * Retrieve an authenticated akeneo php client
     *
     * @return AkeneoPimClientInterface|false
     */
    public function getAkeneoApiClient()
    {
        $baseUri = $this->configHelper->getAkeneoApiBaseUrl();
        $clientId = $this->configHelper->getAkeneoApiClientId();
        $secret = $this->configHelper->getAkeneoApiClientSecret();
        $username = $this->configHelper->getAkeneoApiUsername();
        $password = $this->configHelper->getAkeneoApiPassword();

        if (!$baseUri || !$clientId || !$secret || !$username || !$password) {
            return false;
        }

        $akeneoClientBuilder = $this->clientBuilderFactory->create(['baseUri' => $baseUri]);
        $akeneoClientBuilder->setHttpClient(new Client());
        $akeneoClientBuilder->setStreamFactory(new StreamFactory());
        $akeneoClientBuilder->setRequestFactory(new RequestFactory());

        return $akeneoClientBuilder->buildAuthenticatedByPassword($clientId, $secret, $username, $password);
    }
}
