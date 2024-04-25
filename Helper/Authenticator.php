<?php

declare(strict_types=1);

namespace Akeneo\Connector\Helper;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\AkeneoPimClientBuilder;
use Akeneo\Connector\Helper\Config as ConfigHelper;
use Exception;
use Http\Factory\Guzzle\StreamFactory;
use Http\Factory\Guzzle\RequestFactory;
use Symfony\Component\HttpClient\Psr18Client;

/**
 * Class Authenticator
 *
 * @package   Akeneo\Connector\Helper
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Authenticator
{
    /**
     * This variable contains a ConfigHelper
     *
     * @var ConfigHelper $configHelper
     */
    protected Config $configHelper;

    /**
     * @var AkeneoPimClientInterface|null $akeneoClient
     */
    private ?AkeneoPimClientInterface $akeneoClient = null;

    /**
     * Authenticator constructor
     *
     * @param Config $configHelper
     */
    public function __construct(
        ConfigHelper $configHelper
    ) {
        $this->configHelper = $configHelper;
    }

    /**
     * Retrieve an authenticated akeneo php client
     *
     * @return AkeneoPimClientInterface|null
     * @throws Exception
     */
    public function getAkeneoApiClient(): ?AkeneoPimClientInterface
    {
        if ($this->akeneoClient !== null) {
            return $this->akeneoClient;
        }

        $baseUri = $this->configHelper->getAkeneoApiBaseUrl();
        $clientId = $this->configHelper->getAkeneoApiClientId();
        $secret = $this->configHelper->getAkeneoApiClientSecret();
        $username = $this->configHelper->getAkeneoApiUsername();
        $password = $this->configHelper->getAkeneoApiPassword();

        if (!$baseUri || !$clientId || !$secret || !$username || !$password) {
            return null;
        }

        $akeneoClientBuilder = new AkeneoPimClientBuilder($baseUri);
        $akeneoClientBuilder->setHttpClient(new Psr18Client());
        $akeneoClientBuilder->setStreamFactory(new StreamFactory());
        $akeneoClientBuilder->setRequestFactory(new RequestFactory());

        $this->akeneoClient = $akeneoClientBuilder->buildAuthenticatedByPassword($clientId, $secret, $username, $password);

        return $this->akeneoClient;
    }
}
