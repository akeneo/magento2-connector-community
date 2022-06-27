<?php

declare(strict_types=1);

namespace Akeneo\Connector\Model\Source\Filters;

use Akeneo\Connector\Helper\Authenticator;
use Akeneo\Connector\Helper\Config as ConfigHelper;
use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\Option\ArrayInterface;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Attribute implements ArrayInterface
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
     * Attribute constructor
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
    public function getAttributes()
    {
        /** @var array $attributes */
        $attributes = [];

        try {
            /** @var AkeneoPimClientInterface $client */
            $client = $this->akeneoAuthenticator->getAkeneoApiClient();

            if (empty($client)) {
                return $attributes;
            }

            /** @var string|int $paginationSize */
            $paginationSize = $this->configHelper->getPaginationSize();
            /** @var ResourceCursorInterface $akeneoAttributes */
            $akeneoAttributes = $client->getAttributeApi()->all($paginationSize);
            /** @var mixed[] $attribute */
            foreach ($akeneoAttributes as $attribute) {
                if (!isset($attribute['code'])) {
                    continue;
                }
                $attributes[$attribute['code']] = $attribute['code'];
            }
        } catch (\Exception $exception) {
            $this->logger->warning($exception->getMessage());
        }

        return $attributes;
    }

    /**
     * Retrieve options value and label in an array
     *
     * @return array
     */
    public function toOptionArray()
    {
        /** @var array $attributes */
        $attributes = $this->getAttributes();
        /** @var array $optionArray */
        $optionArray = [];
        /**
         * @var int    $optionValue
         * @var string $optionLabel
         */
        foreach ($attributes as $optionValue => $optionLabel) {
            $optionArray[] = [
                'value' => $optionValue,
                'label' => $optionLabel,
            ];
        }

        return $optionArray;
    }
}
