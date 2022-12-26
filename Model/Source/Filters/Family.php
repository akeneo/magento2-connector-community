<?php

declare(strict_types=1);

namespace Akeneo\Connector\Model\Source\Filters;

use Akeneo\Connector\Helper\Config as ConfigHelper;
use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Magento\Framework\Option\ArrayInterface;
use Psr\Log\LoggerInterface as Logger;
use Akeneo\Connector\Helper\Authenticator;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Family implements ArrayInterface
{
    /**
     * This variable contains a mixed value
     *
     * @var Authenticator $akeneoAuthenticator
     */
    protected $akeneoAuthenticator;
    /**
     * Description $logger field
     *
     * @var Logger $logger
     */
    protected $logger;
    /**
     * This variable contains a ConfigHelper
     *
     * @var ConfigHelper $configHelper
     */
    protected $configHelper;

    /**
     * Family constructor
     *
     * @param Authenticator $akeneoAuthenticator
     * @param Logger        $logger
     * @param ConfigHelper  $configHelper
     */
    public function __construct(
        Authenticator $akeneoAuthenticator,
        Logger $logger,
        ConfigHelper $configHelper
    ) {
        $this->akeneoAuthenticator = $akeneoAuthenticator;
        $this->logger              = $logger;
        $this->configHelper        = $configHelper;
    }

    /**
     * Initialize options
     *
     * @return ResourceCursorInterface|array
     */
    public function getFamilies()
    {
        /** @var array $families */
        $families = [];

        try {
            /** @var AkeneoPimClientInterface $client */
            $client = $this->akeneoAuthenticator->getAkeneoApiClient();

            if (empty($client)) {
                return $families;
            }

            /** @var string|int $paginationSize */
            $paginationSize = $this->configHelper->getPaginationSize();
            /** @var ResourceCursorInterface $families */
            $akeneoFamilies = $client->getFamilyApi()->all($paginationSize);
            /** @var mixed[] $family */
            foreach ($akeneoFamilies as $family) {
                if (!isset($family['code'])) {
                    continue;
                }
                $families[$family['code']] = $family['code'];
            }
        } catch (\Exception $exception) {
            $this->logger->warning($exception->getMessage());
        }

        return $families;
    }

    /**
     * Retrieve options value and label in an array
     *
     * @return array
     */
    public function toOptionArray()
    {
        /** @var array $families */
        $families = $this->getFamilies();
        /** @var array $optionArray */
        $optionArray = [];
        /**
         * @var int    $optionValue
         * @var string $optionLabel
         */
        foreach ($families as $optionValue => $optionLabel) {
            $optionArray[] = [
                'value' => $optionValue,
                'label' => $optionLabel,
            ];
        }

        return $optionArray;
    }
}
