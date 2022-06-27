<?php

declare(strict_types=1);

namespace Akeneo\Connector\Helper;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\AkeneoPimClientBuilder;
use Akeneo\Connector\Helper\Config as ConfigHelper;
use Http\Factory\Guzzle\StreamFactory;
use Http\Factory\Guzzle\RequestFactory;
use Symfony\Component\HttpClient\Psr18Client;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Authenticator
{
    /**
     * This variable contains a ConfigHelper
     *
     * @var ConfigHelper $configHelper
     */
    protected $configHelper;

    /**
     * Authenticator constructor
     *
     * @param ConfigHelper $configHelper
     */
    public function __construct(
        ConfigHelper $configHelper
    ) {
        $this->configHelper = $configHelper;
    }

    /**
     * Retrieve an authenticated akeneo php client
     *
     * @return AkeneoPimClientInterface|false
     */
    public function getAkeneoApiClient()
    {
        /** @var string $baseUri */
        $baseUri = $this->configHelper->getAkeneoApiBaseUrl();
        /** @var string $clientId */
        $clientId = $this->configHelper->getAkeneoApiClientId();
        /** @var string $secret */
        $secret = $this->configHelper->getAkeneoApiClientSecret();
        /** @var string $username */
        $username = $this->configHelper->getAkeneoApiUsername();
        /** @var string $password */
        $password = $this->configHelper->getAkeneoApiPassword();

        if (!$baseUri || !$clientId || !$secret || !$username || !$password) {
            return false;
        }

        /** @var AkeneoPimClientBuilder $akeneoClientBuilder */
        $akeneoClientBuilder = new AkeneoPimClientBuilder($baseUri);

        $akeneoClientBuilder->setHttpClient(new Psr18Client());
        $akeneoClientBuilder->setStreamFactory(new StreamFactory());
        $akeneoClientBuilder->setRequestFactory(new RequestFactory());

        return $akeneoClientBuilder->buildAuthenticatedByPassword($clientId, $secret, $username, $password);
    }
}
