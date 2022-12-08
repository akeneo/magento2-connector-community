<?php

declare(strict_types=1);

namespace Akeneo\Connector\Model\Source\Attribute;

use Akeneo\Connector\Helper\AttributeFilters;
use Akeneo\Connector\Helper\Authenticator;
use Akeneo\Connector\Helper\Config as ConfigHelper;
use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Metrics extends AbstractSource
{
    /**
     * This variable contains a mixed value
     *
     * @var Authenticator $akeneoAuthenticator
     */
    protected $akeneoAuthenticator;
    /**
     * This variable contains a AkeneoPimClientInterface
     *
     * @var AkeneoPimClientInterface $akeneoClient
     */
    protected $akeneoClient;
    /**
     * This variable contains a ConfigHelper
     *
     * @var ConfigHelper $configHelper
     */
    protected $configHelper;
    /**
     * This variable contains a AttributeFilters
     *
     * @var AttributeFilters $attributeFilters
     */
    protected $attributeFilters;

    /**
     * Metrics constructor
     *
     * @param Authenticator    $akeneoAuthenticator
     * @param AttributeFilters $attributeFilters
     * @param ConfigHelper     $configHelper
     */
    public function __construct(
        Authenticator $akeneoAuthenticator,
        AttributeFilters $attributeFilters,
        ConfigHelper $configHelper
    ) {
        $this->akeneoAuthenticator = $akeneoAuthenticator;
        $this->attributeFilters    = $attributeFilters;
        $this->configHelper        = $configHelper;
    }

    /**
     * Generate array of all metrics options from connected akeneo
     *
     * @return array
     */
    public function getAllOptions()
    {
        /** @var ResourceCursorInterface|mixed[] $attributes */
        $attributes = $this->getAttributes();

        if (!$attributes) {
            return $this->_options;
        }

        foreach ($attributes as $attribute) {
            if ($attribute['type'] != AttributeFilters::ATTRIBUTE_TYPE_CATALOG_METRIC) {
                continue;
            }
            $this->_options[] = ['label' => $attribute['code'], 'value' => $attribute['code']];
        }

        return $this->_options;
    }

    /**
     * Generate cursor interface of pim metrics list
     *
     * @return ResourceCursorInterface|mixed[]
     */
    public function getAttributes()
    {
        try {
            /** @var AkeneoPimClientInterface $akeneoClient */
            $akeneoClient = $this->akeneoAuthenticator->getAkeneoApiClient();

            if (!$akeneoClient) {
                return $this->_options;
            }
            /** @var string|int $paginationSize */
            $paginationSize = $this->configHelper->getPaginationSize();

            /** @var string[] $attributeTypeFilter */
            $attributeTypeFilter = $this->attributeFilters->createAttributeTypeFilter(
                [AttributeFilters::ATTRIBUTE_TYPE_CATALOG_METRIC],
                true
            );

            return $akeneoClient->getAttributeApi()->all($paginationSize, $attributeTypeFilter);
        } catch (\Exception $exception) {
            return [];
        }
    }

    /**
     * Get list of all metric attributes only
     *
     * @return array|string[]
     */
    public function getMetricsAttributes()
    {
        /** @var string[] $metrics */
        $metrics = [];
        /** @var ResourceCursorInterface|mixed[] $attributes */
        $attributes = $this->getAttributes();

        if (!$attributes) {
            return $this->_options;
        }

        foreach ($attributes as $attribute) {
            if ($attribute['type'] != AttributeFilters::ATTRIBUTE_TYPE_CATALOG_METRIC) {
                continue;
            }

            $metrics[] = $attribute['code'];
        }

        return $metrics;
    }
}
