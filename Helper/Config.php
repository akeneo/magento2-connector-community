<?php

namespace Akeneo\Connector\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\Encryptor;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Directory\Model\Currency;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Store\Model\StoreManagerInterface;
use Magento\CatalogInventory\Model\Configuration as CatalogInventoryConfiguration;
use Magento\Catalog\Model\Product\Media\Config as MediaConfig;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\File\Uploader;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Catalog\Helper\Product as ProductHelper;

/**
 * Class Config
 *
 * @category  Class
 * @package   Akeneo\Connector\Helper
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Config extends AbstractHelper
{
    /** Config keys */
    const AKENEO_API_BASE_URL = 'akeneo_connector/akeneo_api/base_url';
    const AKENEO_API_USERNAME = 'akeneo_connector/akeneo_api/username';
    const AKENEO_API_PASSWORD = 'akeneo_connector/akeneo_api/password';
    const AKENEO_API_CLIENT_ID = 'akeneo_connector/akeneo_api/client_id';
    const AKENEO_API_CLIENT_SECRET = 'akeneo_connector/akeneo_api/client_secret';
    const AKENEO_API_PAGINATION_SIZE = 'akeneo_connector/akeneo_api/pagination_size';
    const AKENEO_API_ADMIN_CHANNEL = 'akeneo_connector/akeneo_api/admin_channel';
    const AKENEO_API_WEBSITE_MAPPING = 'akeneo_connector/akeneo_api/website_mapping';
    const PRODUCTS_FILTERS_MODE = 'akeneo_connector/products_filters/mode';
    const PRODUCTS_FILTERS_COMPLETENESS_TYPE = 'akeneo_connector/products_filters/completeness_type';
    const PRODUCTS_FILTERS_COMPLETENESS_VALUE = 'akeneo_connector/products_filters/completeness_value';
    const PRODUCTS_FILTERS_COMPLETENESS_LOCALES = 'akeneo_connector/products_filters/completeness_locales';
    const PRODUCTS_FILTERS_STATUS = 'akeneo_connector/products_filters/status';
    const PRODUCTS_FILTERS_FAMILIES = 'akeneo_connector/products_filters/families';
    const PRODUCTS_FILTERS_UPDATED_MODE = 'akeneo_connector/products_filters/updated_mode';
    const PRODUCTS_FILTERS_UPDATED_LOWER = 'akeneo_connector/products_filters/updated_lower';
    const PRODUCTS_FILTERS_UPDATED_GREATER = 'akeneo_connector/products_filters/updated_greater';
    const PRODUCTS_FILTERS_UPDATED_BETWEEN_AFTER = 'akeneo_connector/products_filters/updated_between_after';
    const PRODUCTS_FILTERS_UPDATED_BETWEEN_BEFORE = 'akeneo_connector/products_filters/updated_between_before';
    const PRODUCTS_FILTERS_UPDATED_SINCE = 'akeneo_connector/products_filters/updated';
    const PRODUCTS_FILTERS_ADVANCED_FILTER = 'akeneo_connector/products_filters/advanced_filter';
    const PRODUCTS_CATEGORY_IS_ACTIVE = 'akeneo_connector/category/is_active';
    const PRODUCTS_CATEGORY_INCLUDE_IN_MENU = 'akeneo_connector/category/include_in_menu';
    const PRODUCTS_CATEGORY_IS_ANCHOR = 'akeneo_connector/category/is_anchor';
    const PRODUCTS_CATEGORY_CATEGORIES = 'akeneo_connector/category/categories';
    const PRODUCT_ATTRIBUTE_MAPPING = 'akeneo_connector/product/attribute_mapping';
    const PRODUCT_WEBSITE_ATTRIBUTE = 'akeneo_connector/product/website_attribute';
    const PRODUCT_CONFIGURABLE_ATTRIBUTES = 'akeneo_connector/product/configurable_attributes';
    const PRODUCT_PRODUCT_MODEL_BATCH_SIZE = 'akeneo_connector/product/product_model_batch_size';
    const PRODUCT_PRODUCT_MODEL_UPDATE_LENGTH = 'akeneo_connector/product/product_model_update_length';
    const PRODUCT_TAX_CLASS = 'akeneo_connector/product/tax_class';
    const PRODUCT_URL_GENERATION_ENABLED = 'akeneo_connector/product/url_generation_enabled';
    const PRODUCT_MEDIA_ENABLED = 'akeneo_connector/product/media_enabled';
    const PRODUCT_MEDIA_IMAGES = 'akeneo_connector/product/media_images';
    const PRODUCT_MEDIA_GALLERY = 'akeneo_connector/product/media_gallery';
    const PRODUCT_METRICS = 'akeneo_connector/product/metrics';
    const ATTRIBUTE_TYPES = 'akeneo_connector/attribute/types';
    /**
     * @var int PAGINATION_SIZE_DEFAULT_VALUE
     */
    const PAGINATION_SIZE_DEFAULT_VALUE = 10;
    /**
     * @var int PRODUCT_PRODUCT_MODEL_BATCH_SIZE_DEFAULT_VALUE
     */
    const PRODUCT_PRODUCT_MODEL_BATCH_SIZE_DEFAULT_VALUE = 500;
    /**
     * @var int PRODUCT_PRODUCT_MODEL_LENGTH_DEFAULT_VALUE
     */
    const PRODUCT_PRODUCT_MODEL_UPDATE_LENGTH_DEFAULT_VALUE = 5000;
    /**
     * @var int PRODUCT_PRODUCT_MODEL_UPDATE_LENGTH_MINIMUM
     */
    const PRODUCT_PRODUCT_MODEL_UPDATE_LENGTH_MINIMUM = 1000;
    /**
     * This variable contains a Encryptor
     *
     * @var Encryptor $encryptor
     */
    protected $encryptor;
    /**
     * This variable contains a Serializer
     *
     * @var Serializer $serializer
     */
    protected $serializer;
    /**
     * This variable contains a EavConfig
     *
     * @var EavConfig $eavConfig
     */
    protected $eavConfig;
    /**
     * This variable contains a StoreManagerInterface
     *
     * @var StoreManagerInterface $storeManager
     */
    protected $storeManager;
    /**
     * This variable contains a CatalogInventoryConfiguration
     *
     * @var CatalogInventoryConfiguration $catalogInventoryConfiguration
     */
    protected $catalogInventoryConfiguration;
    /**
     * This variable contains a MediaConfig
     *
     * @var MediaConfig $mediaConfig
     */
    protected $mediaConfig;
    /**
     * This variable contains a WriteInterface
     *
     * @var WriteInterface $mediaDirectory
     */
    protected $mediaDirectory;

    /**
     * Config constructor
     *
     * @param Context                       $context
     * @param Encryptor                     $encryptor
     * @param Serializer                    $serializer
     * @param EavConfig                     $eavConfig
     * @param StoreManagerInterface         $storeManager
     * @param CatalogInventoryConfiguration $catalogInventoryConfiguration
     * @param Filesystem                    $filesystem
     * @param MediaConfig                   $mediaConfig
     */
    public function __construct(
        Context $context,
        Encryptor $encryptor,
        Serializer $serializer,
        EavConfig $eavConfig,
        StoreManagerInterface $storeManager,
        CatalogInventoryConfiguration $catalogInventoryConfiguration,
        Filesystem $filesystem,
        MediaConfig $mediaConfig
    ) {
        parent::__construct($context);

        $this->encryptor                     = $encryptor;
        $this->serializer                    = $serializer;
        $this->eavConfig                     = $eavConfig;
        $this->storeManager                  = $storeManager;
        $this->mediaConfig                   = $mediaConfig;
        $this->catalogInventoryConfiguration = $catalogInventoryConfiguration;
        $this->mediaDirectory                = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
    }

    /**
     * Retrieve Akeneo base URL
     *
     * @return string
     */
    public function getAkeneoApiBaseUrl()
    {
        return $this->scopeConfig->getValue(self::AKENEO_API_BASE_URL);
    }

    /**
     * Retrieve Akeneo username
     *
     * @return string
     */
    public function getAkeneoApiUsername()
    {
        return $this->scopeConfig->getValue(self::AKENEO_API_USERNAME);
    }

    /**
     * Retrieve Akeneo password
     *
     * @return string
     */
    public function getAkeneoApiPassword()
    {
        /** @var string $password */
        $password = $this->scopeConfig->getValue(self::AKENEO_API_PASSWORD);

        return $this->encryptor->decrypt($password);
    }

    /**
     * Retrieve Akeneo client_id
     *
     * @return string
     */
    public function getAkeneoApiClientId()
    {
        return $this->scopeConfig->getValue(self::AKENEO_API_CLIENT_ID);
    }

    /**
     * Retrieve Akeneo client_secret
     *
     * @return string
     */
    public function getAkeneoApiClientSecret()
    {
        return $this->scopeConfig->getValue(self::AKENEO_API_CLIENT_SECRET);
    }

    /**
     * Retrieve the filter mode used
     *
     * @return string
     * @see \Akeneo\Connector\Model\Source\Filters\Mode
     *
     */
    public function getFilterMode()
    {
        return $this->scopeConfig->getValue(self::PRODUCTS_FILTERS_MODE);
    }

    /**
     * Retrieve the type of filter to apply on the completeness
     *
     * @return string
     * @see \Akeneo\Connector\Model\Source\Filters\Completeness
     *
     */
    public function getCompletenessTypeFilter()
    {
        return $this->scopeConfig->getValue(self::PRODUCTS_FILTERS_COMPLETENESS_TYPE);
    }

    /**
     * Retrieve the value to filter the completeness
     *
     * @return string
     */
    public function getCompletenessValueFilter()
    {
        return $this->scopeConfig->getValue(self::PRODUCTS_FILTERS_COMPLETENESS_VALUE);
    }

    /**
     * Retrieve the locales to apply the completeness filter on
     *
     * @return string
     */
    public function getCompletenessLocalesFilter()
    {
        return $this->scopeConfig->getValue(self::PRODUCTS_FILTERS_COMPLETENESS_LOCALES);
    }

    /**
     * Retrieve the status filter
     *
     * @return string
     * @see \Akeneo\Connector\Model\Source\Filters\Status
     *
     */
    public function getStatusFilter()
    {
        return $this->scopeConfig->getValue(self::PRODUCTS_FILTERS_STATUS);
    }

    /**
     * Retrieve updated mode
     *
     * @return string
     */
    public function getUpdatedMode()
    {
        return $this->scopeConfig->getValue(self::PRODUCTS_FILTERS_UPDATED_MODE);
    }

    /**
     * Retrieve the updated before filter
     *
     * @return string
     */
    public function getUpdatedLowerFilter()
    {
        return $this->scopeConfig->getValue(self::PRODUCTS_FILTERS_UPDATED_LOWER);
    }

    /**
     * Retrieve the updated after filter
     *
     * @return string
     */
    public function getUpdatedGreaterFilter()
    {
        return $this->scopeConfig->getValue(self::PRODUCTS_FILTERS_UPDATED_GREATER);
    }

    /**
     * Retrieve the updated after for between filter
     *
     * @return string
     */
    public function getUpdatedBetweenAfterFilter()
    {
        return $this->scopeConfig->getValue(self::PRODUCTS_FILTERS_UPDATED_BETWEEN_AFTER);
    }

    /**
     * Retrieve the updated before for between filter
     *
     * @return string
     */
    public function getUpdatedBetweenBeforeFilter()
    {
        return $this->scopeConfig->getValue(self::PRODUCTS_FILTERS_UPDATED_BETWEEN_BEFORE);
    }

    /**
     * Retrieve the updated since filter
     *
     * @return string
     */
    public function getUpdatedSinceFilter()
    {
        return $this->scopeConfig->getValue(self::PRODUCTS_FILTERS_UPDATED_SINCE);
    }

    /**
     * Retrieve the families to filter the products on
     *
     * @return string
     */
    public function getFamiliesFilter()
    {
        return $this->scopeConfig->getValue(self::PRODUCTS_FILTERS_FAMILIES);
    }

    /**
     * Retrieve the advance filters
     *
     * @return array
     */
    public function getAdvancedFilters()
    {
        $filters = $this->scopeConfig->getValue(self::PRODUCTS_FILTERS_ADVANCED_FILTER);

        return $this->serializer->unserialize($filters);
    }

    /**
     * Retrieve the status of imported categories
     *
     * @return string
     */
    public function getIsCategoryActive()
    {
        return $this->scopeConfig->getValue(self::PRODUCTS_CATEGORY_IS_ACTIVE);
    }

    /**
     * Retrieve the inclusion in menu of imported categories
     *
     * @return string
     */
    public function getIsCategoryInMenu()
    {
        return $this->scopeConfig->getValue(self::PRODUCTS_CATEGORY_INCLUDE_IN_MENU);
    }

    /**
     * Retrieve the anchor state of imported categories
     *
     * @return string
     */
    public function getIsCategoryAnchor()
    {
        return $this->scopeConfig->getValue(self::PRODUCTS_CATEGORY_IS_ANCHOR);
    }

    /**
     * Retrieve the categories to filter the category import
     *
     * @return string
     */
    public function getCategoriesFilter()
    {
        return $this->scopeConfig->getValue(self::PRODUCTS_CATEGORY_CATEGORIES);
    }

    /**
     * Get Admin Website Default Channel from configuration
     *
     * @return string
     */
    public function getAdminDefaultChannel()
    {
        return $this->scopeConfig->getValue(self::AKENEO_API_ADMIN_CHANNEL);
    }

    /**
     * Retrieve the name of the website association attribute
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getWebsiteAttribute()
    {
        return $this->scopeConfig->getValue(self::PRODUCT_WEBSITE_ATTRIBUTE);
    }

    /**
     * Retrieve website mapping
     *
     * @param bool $withDefault
     *
     * @return mixed[]
     * @throws \Exception
     *
     */
    public function getWebsiteMapping($withDefault = true)
    {
        /** @var mixed[] $mapping */
        $mapping = [];

        if ($withDefault === true) {
            /** @var string $adminChannel */
            $adminChannel = $this->getAdminDefaultChannel();
            if (empty($adminChannel)) {
                throw new \Exception(__('No channel found for Admin website channel configuration.'));
            }

            $mapping[] = [
                'channel' => $adminChannel,
                'website' => $this->storeManager->getWebsite(0)->getCode(),
            ];
        }

        /** @var string $websiteMapping */
        $websiteMapping = $this->scopeConfig->getValue(self::AKENEO_API_WEBSITE_MAPPING);
        if (empty($websiteMapping)) {
            return $mapping;
        }

        /** @var mixed[] $websiteMapping */
        $websiteMapping = $this->serializer->unserialize($websiteMapping);
        if (empty($websiteMapping) || !is_array($websiteMapping)) {
            return $mapping;
        }

        $mapping = array_merge($mapping, $websiteMapping);

        return $mapping;
    }

    /**
     * Get mapped channels
     *
     * @return string[]
     */
    public function getMappedChannels()
    {
        /** @var mixed[] $mapping */
        $mapping = $this->getWebsiteMapping();
        /** @var string[] $channels */
        $channels = array_column($mapping, 'channel', 'channel');

        return $channels;
    }

    /**
     * Retrieve default locale
     *
     * @param int $storeId
     *
     * @return string
     */
    public function getDefaultLocale($storeId = null)
    {
        return $this->scopeConfig->getValue(
            DirectoryHelper::XML_PATH_DEFAULT_LOCALE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Retrieve default currency
     *
     * @param int $storeId
     *
     * @return string
     */
    public function getDefaultCurrency($storeId = null)
    {
        return $this->scopeConfig->getValue(
            Currency::XML_PATH_CURRENCY_DEFAULT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Retrieve pagination size
     *
     * @return string|int
     */
    public function getPanigationSize()
    {
        /** @var string|int $paginationSize */
        $paginationSize = $this->scopeConfig->getValue(self::AKENEO_API_PAGINATION_SIZE);
        if (!$paginationSize) {
            $paginationSize = self::PAGINATION_SIZE_DEFAULT_VALUE;
        }

        return $paginationSize;
    }

    /**
     * Retrieve entity type id from entity name
     *
     * @param string $entity
     *
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getEntityTypeId($entity)
    {
        return $this->eavConfig->getEntityType($entity)->getEntityTypeId();
    }

    /**
     * Retrieve attribute by code
     *
     * @param string $entityType
     * @param string $code
     *
     * @return AbstractAttribute
     */
    public function getAttribute($entityType, $code)
    {
        return $this->eavConfig->getAttribute($entityType, $code);
    }

    /**
     * Retrieve stores default tax class
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductTaxClasses()
    {
        /** @var array $stores */
        $stores = $this->storeManager->getStores(true);
        /** @var array $result */
        $result = [];

        /** @var string|array $classes */
        $classes = $this->scopeConfig->getValue(self::PRODUCT_TAX_CLASS);
        if (!$classes) {
            return $result;
        }

        $classes = $this->serializer->unserialize($classes);
        if (!is_array($classes)) {
            return $result;
        }

        /** @var array $class */
        foreach ($classes as $class) {
            if (!isset($class['website'])) {
                continue;
            }
            if (!isset($class['tax_class'])) {
                continue;
            }

            if ($this->getDefaultWebsiteId() === $class['website']) {
                $result[0] = $class['tax_class'];
            }

            /** @var StoreInterface $store */
            foreach ($stores as $store) {
                if ($store->getWebsiteId() === $class['website']) {
                    $result[$store->getId()] = $class['tax_class'];
                }
            }
        }

        return $result;
    }

    /**
     * Retrieve default website id
     *
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getDefaultWebsiteId()
    {
        return $this->storeManager->getStore()->getWebsiteId();
    }

    /**
     * Retrieve default scope id used by the catalog inventory module when saving an entity
     *
     * @return int
     */
    public function getDefaultScopeId()
    {
        return $this->catalogInventoryConfiguration->getDefaultScopeId();
    }

    /**
     * Description isMediaImportEnabled function
     *
     * @return bool
     */
    public function isMediaImportEnabled()
    {
        return $this->scopeConfig->isSetFlag(self::PRODUCT_MEDIA_ENABLED);
    }

    /**
     * Description isUrlGenerationEnabled function
     *
     * @return bool
     */
    public function isUrlGenerationEnabled()
    {
        return $this->scopeConfig->isSetFlag(self::PRODUCT_URL_GENERATION_ENABLED);
    }

    /**
     * Retrieve media attribute column
     *
     * @return array
     */
    public function getMediaImportImagesColumns()
    {
        /** @var array $images */
        $images = [];
        /** @var string $config */
        $config = $this->scopeConfig->getValue(self::PRODUCT_MEDIA_IMAGES);
        if (!$config) {
            return $images;
        }

        /** @var array $media */
        $media = $this->serializer->unserialize($config);
        if (!$media) {
            return $images;
        }

        return $media;
    }

    /**
     * Retrieve media attribute column
     *
     * @return array
     */
    public function getMediaImportGalleryColumns()
    {
        /** @var array $images */
        $images = [];
        /** @var string $config */
        $config = $this->scopeConfig->getValue(self::PRODUCT_MEDIA_GALLERY);
        if (!$config) {
            return $images;
        }

        /** @var array $media */
        $media = $this->serializer->unserialize($config);
        if (!$media) {
            return $images;
        }

        foreach ($media as $image) {
            if (!isset($image['attribute'])) {
                continue;
            }
            $images[] = $image['attribute'];
        }

        return $images;
    }

    /**
     * Retrieve metrics columns by define return needed
     *
     * @param bool|null $returnVariant
     * @param bool|null $returnConcat
     *
     * @return array|mixed[]
     */
    public function getMetricsColumns($returnVariant = null, $returnConcat = null)
    {
        /** @var array $metrics */
        $metrics = [];
        /** @var string $config */
        $config = $this->scopeConfig->getValue(self::PRODUCT_METRICS);
        if (!$config) {
            return $metrics;
        }

        /** @var array $unserializeMetrics */
        $unserializeMetrics = $this->serializer->unserialize($config);
        if (!$unserializeMetrics) {
            return $metrics;
        }

        /** @var mixed[] $metricsColumns */
        $metricsColumns = [];
        foreach ($unserializeMetrics as $unserializeMetric) {
            if ($returnVariant === true && $returnConcat === null && $unserializeMetric['is_variant'] == 0) {
                continue;
            }
            if ($returnVariant === null && $returnConcat === true && $unserializeMetric['is_concat'] == 0) {
                continue;
            }
            if ($returnVariant === true && $returnConcat === false && $unserializeMetric['is_concat'] == 1) {
                continue;
            }
            if ($returnVariant === false && $returnConcat === true && $unserializeMetric['is_variant'] == 1) {
                continue;
            }

            /** @var string $metricAttributeCode */
            $metricAttributeCode = $unserializeMetric['akeneo_metrics'];

            $metricsColumns[] = $metricAttributeCode;
        }

        return $metricsColumns;
    }

    /**
     * Check if media file exists
     *
     * @param string $filename
     *
     * @return bool
     */
    public function mediaFileExists($filename)
    {
        return $this->mediaDirectory->isFile($this->mediaConfig->getMediaPath($this->getMediaFilePath($filename)));
    }

    /**
     * Retrieve media directory path
     *
     * @param string $filename
     * @param string $content
     *
     * @return void
     */
    public function saveMediaFile($filename, $content)
    {
        if (!$this->mediaFileExists($filename)) {
            $this->mediaDirectory->writeFile(
                $this->mediaConfig->getMediaPath($this->getMediaFilePath($filename)),
                $content
            );
        }
    }

    /**
     * Retrieve media file path
     *
     * @param string $filename
     *
     * @return string
     */
    public function getMediaFilePath($filename)
    {
        return Uploader::getDispretionPath($filename) . '/' . Uploader::getCorrectFileName($filename);
    }

    /**
     * Retrieve if category is used in product URL
     *
     * @param int $storeId
     *
     * @return bool
     */
    public function isCategoryUsedInProductUrl($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            ProductHelper::XML_PATH_PRODUCT_URL_USE_CATEGORY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if url_key attribute is mapped with PIM attribute
     *
     * @return bool
     */
    public function isUrlKeyMapped()
    {
        /** @var mixed $matches */
        $matches = $this->getAttributeMapping();
        if (!is_array($matches)) {
            return false;
        }

        /** @var mixed[] $match */
        foreach ($matches as $match) {
            if (!isset($match['akeneo_attribute'], $match['magento_attribute'])) {
                continue;
            }

            /** @var string $magentoAttribute */
            $magentoAttribute = $match['magento_attribute'];
            if ($magentoAttribute === 'url_key') {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieve product_model batch size
     *
     * @return int
     */
    public function getAdvancedPmBatchSize()
    {
        /** @var int $advancedPmBatchSize */
        $advancedPmBatchSize = $this->scopeConfig->getValue(self::PRODUCT_PRODUCT_MODEL_BATCH_SIZE);
        if (filter_var($advancedPmBatchSize, FILTER_VALIDATE_INT) === false) {
            $advancedPmBatchSize = self::PRODUCT_PRODUCT_MODEL_BATCH_SIZE_DEFAULT_VALUE;
        }

        return $advancedPmBatchSize;
    }

    /**
     * Retrieve product_model update length
     *
     * @return int
     */
    public function getAdvancedPmUpdateLength()
    {
        /** @var int $advancedPmUpdateLength */
        $advancedPmUpdateLength = $this->scopeConfig->getValue(self::PRODUCT_PRODUCT_MODEL_UPDATE_LENGTH);
        if ((filter_var($advancedPmUpdateLength, FILTER_VALIDATE_INT)) === false || ($advancedPmUpdateLength < self::PRODUCT_PRODUCT_MODEL_UPDATE_LENGTH_MINIMUM)) {
            $advancedPmUpdateLength = self::PRODUCT_PRODUCT_MODEL_UPDATE_LENGTH_DEFAULT_VALUE;
        }

        return $advancedPmUpdateLength;
    }

    /**
     * Get the attribute mapping, with lowered values
     *
     * @return mixed
     */
    public function getAttributeMapping()
    {
        /** @var mixed $matches */
        $matches = $this->scopeConfig->getValue(self::PRODUCT_ATTRIBUTE_MAPPING);
        $matches = $this->serializer->unserialize($matches);
        /** @var mixed $loweredMatchs */
        $loweredMatches = [];
        /** @var string[] $match */
        foreach ($matches as $match) {
            $match           = array_map('strtolower', $match);
            $loweredMatches[] = $match;
        }

        return $loweredMatches;
    }

    /**
     * Returns default attribute-set id for given entity
     *
     * @param string $entity
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getDefaultAttributeSetId($entity)
    {
        return $this->eavConfig->getEntityType($entity)->getDefaultAttributeSetId();
    }
}
