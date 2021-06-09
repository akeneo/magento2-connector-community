<?php

namespace Akeneo\Connector\Helper;

use Magento\Catalog\Model\Product\Link;
use Akeneo\Connector\Model\Source\Edition;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\Encryptor;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Exception\FileSystemException;
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
use Magento\Framework\App\Config\ScopeConfigInterface;

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
class Config
{
    /**
     * API URL config path
     *
     * @var string AKENEO_API_BASE_URL
     */
    const AKENEO_API_BASE_URL = 'akeneo_connector/akeneo_api/base_url';
    /**
     * API user name config path
     *
     * @var string AKENEO_API_USERNAME
     */
    const AKENEO_API_USERNAME = 'akeneo_connector/akeneo_api/username';
    /**
     * API password config path
     *
     * @var string AKENEO_API_PASSWORD
     */
    const AKENEO_API_PASSWORD = 'akeneo_connector/akeneo_api/password';
    /**
     * API client id config path
     *
     * @var string AKENEO_API_CLIENT_ID
     */
    const AKENEO_API_CLIENT_ID = 'akeneo_connector/akeneo_api/client_id';
    /**
     * API secret key config path
     *
     * @var string AKENEO_API_CLIENT_SECRET
     */
    const AKENEO_API_CLIENT_SECRET = 'akeneo_connector/akeneo_api/client_secret';
    /**
     * API edition config path
     *
     * @var string AKENEO_API_EDITION
     */
    const AKENEO_API_EDITION = 'akeneo_connector/akeneo_api/edition';
    /**
     * API pagination size config path
     *
     * @var string AKENEO_API_PAGINATION_SIZE
     */
    const AKENEO_API_PAGINATION_SIZE = 'akeneo_connector/akeneo_api/pagination_size';
    /**
     * API admin channel config path
     *
     * @var string AKENEO_API_ADMIN_CHANNEL
     */
    const AKENEO_API_ADMIN_CHANNEL = 'akeneo_connector/akeneo_api/admin_channel';
    /**
     * API website mapping with channels config path
     *
     * @var string AKENEO_API_WEBSITE_MAPPING
     */
    const AKENEO_API_WEBSITE_MAPPING = 'akeneo_connector/akeneo_api/website_mapping';
    /**
     * Product filters mode config path
     *
     * @var string PRODUCTS_FILTERS_MODE
     */
    const PRODUCTS_FILTERS_MODE = 'akeneo_connector/products_filters/mode';
    /**
     * Product filters completeness type config path
     *
     * @var string PRODUCTS_FILTERS_COMPLETENESS_TYPE
     */
    const PRODUCTS_FILTERS_COMPLETENESS_TYPE = 'akeneo_connector/products_filters/completeness_type';
    /**
     * Product filters completeness value config path
     *
     * @var string PRODUCTS_FILTERS_COMPLETENESS_VALUE
     */
    const PRODUCTS_FILTERS_COMPLETENESS_VALUE = 'akeneo_connector/products_filters/completeness_value';
    /**
     * Product filters completeness locales config path
     *
     * @var string PRODUCTS_FILTERS_COMPLETENESS_LOCALES
     */
    const PRODUCTS_FILTERS_COMPLETENESS_LOCALES = 'akeneo_connector/products_filters/completeness_locales';
    /**
     * Product model filters completeness locales config path
     *
     * @var string PRODUCTS_MODEL_FILTERS_COMPLETENESS_LOCALES
     */
    const PRODUCTS_MODEL_FILTERS_COMPLETENESS_LOCALES = 'akeneo_connector/products_filters/model_completeness_locales';
    /**
     * Product model filters completeness type config path
     *
     * @var string PRODUCTS_MODEL_FILTERS_COMPLETENESS_TYPE
     */
    const PRODUCTS_MODEL_FILTERS_COMPLETENESS_TYPE = 'akeneo_connector/products_filters/model_completeness_type';
    /**
     * Product filters status config path
     *
     * @var string PRODUCTS_FILTERS_STATUS
     */
    const PRODUCTS_FILTERS_STATUS = 'akeneo_connector/products_filters/status';
    /**
     * Product filters families config path
     *
     * @var string PRODUCTS_FILTERS_FAMILIES
     */
    const PRODUCTS_FILTERS_FAMILIES = 'akeneo_connector/products_filters/families';
    /**
     * Product filters updated mode config path
     *
     * @var string PRODUCTS_FILTERS_UPDATED_MODE
     */
    const PRODUCTS_FILTERS_UPDATED_MODE = 'akeneo_connector/products_filters/updated_mode';
    /**
     * Product filters updated lower config pathL
     *
     * @var string PRODUCTS_FILTERS_UPDATED_LOWER
     */
    const PRODUCTS_FILTERS_UPDATED_LOWER = 'akeneo_connector/products_filters/updated_lower';
    /**
     * Product filters updated greater config path
     *
     * @var string PRODUCTS_FILTERS_UPDATED_GREATER
     */
    const PRODUCTS_FILTERS_UPDATED_GREATER = 'akeneo_connector/products_filters/updated_greater';
    /**
     * Product filters updated between config path
     *
     * @var string PRODUCTS_FILTERS_UPDATED_BETWEEN_AFTER
     */
    const PRODUCTS_FILTERS_UPDATED_BETWEEN_AFTER = 'akeneo_connector/products_filters/updated_between_after';
    /**
     * Product filters updated between before config path
     *
     * @var string PRODUCTS_FILTERS_UPDATED_BETWEEN_BEFORE
     */
    const PRODUCTS_FILTERS_UPDATED_BETWEEN_BEFORE = 'akeneo_connector/products_filters/updated_between_before';
    /**
     * Product filters updated since config path
     *
     * @var string PRODUCTS_FILTERS_UPDATED_SINCE
     */
    const PRODUCTS_FILTERS_UPDATED_SINCE = 'akeneo_connector/products_filters/updated';
    /**
     * Product advanced filters config path
     *
     * @var string PRODUCTS_FILTERS_ADVANCED_FILTER
     */
    const PRODUCTS_FILTERS_ADVANCED_FILTER = 'akeneo_connector/products_filters/advanced_filter';
    /**
     * Product model advanced filters config path
     *
     * @var string PRODUCTS_MODEL_FILTERS_ADVANCED_FILTER
     */
    const PRODUCTS_MODEL_FILTERS_ADVANCED_FILTER = 'akeneo_connector/products_filters/model_advanced_filter';
    /**
     * Product category is active config path
     *
     * @var string PRODUCTS_CATEGORY_IS_ACTIVE
     */
    const PRODUCTS_CATEGORY_IS_ACTIVE = 'akeneo_connector/category/is_active';
    /**
     * Categories are included in menu config path
     *
     * @var string PRODUCTS_CATEGORY_INCLUDE_IN_MENU
     */
    const PRODUCTS_CATEGORY_INCLUDE_IN_MENU = 'akeneo_connector/category/include_in_menu';
    /**
     * Categories are anchor config path
     *
     * @var string PRODUCTS_CATEGORY_IS_ANCHOR
     */
    const PRODUCTS_CATEGORY_IS_ANCHOR = 'akeneo_connector/category/is_anchor';
    /**
     * Categories to not import config path
     *
     * @var string PRODUCTS_CATEGORY_CATEGORIES
     */
    const PRODUCTS_CATEGORY_CATEGORIES = 'akeneo_connector/category/categories';
    /**
     * Attribute mapping config path
     *
     * @var string PRODUCT_ATTRIBUTE_MAPPING
     */
    const PRODUCT_ATTRIBUTE_MAPPING = 'akeneo_connector/product/attribute_mapping';
    /**
     * Website attribute config path
     *
     * @var string PRODUCT_WEBSITE_ATTRIBUTE
     */
    const PRODUCT_WEBSITE_ATTRIBUTE = 'akeneo_connector/product/website_attribute';
    /**
     * Configurable attribute mapping config path
     *
     * @var string PRODUCT_CONFIGURABLE_ATTRIBUTES
     */
    const PRODUCT_CONFIGURABLE_ATTRIBUTES = 'akeneo_connector/product/configurable_attributes';
    /**
     * Product tax class config path
     *
     * @var string PRODUCT_TAX_CLASS
     */
    const PRODUCT_TAX_CLASS = 'akeneo_connector/product/tax_class';
    /**
     * Product url generation flag config path
     *
     * @var string PRODUCT_URL_GENERATION_ENABLED
     */
    const PRODUCT_URL_GENERATION_ENABLED = 'akeneo_connector/product/url_generation_enabled';
    /**
     * Media import enabled config path
     *
     * @var string PRODUCT_MEDIA_ENABLED
     */
    const PRODUCT_MEDIA_ENABLED = 'akeneo_connector/product/media_enabled';
    /**
     * Media attributes config path
     *
     * @var string PRODUCT_MEDIA_IMAGES
     */
    const PRODUCT_MEDIA_IMAGES = 'akeneo_connector/product/media_images';
    /**
     * Media special images mapping config path
     *
     * @var string PRODUCT_MEDIA_GALLERY
     */
    const PRODUCT_MEDIA_GALLERY = 'akeneo_connector/product/media_gallery';
    /**
     * File import flag config path
     *
     * @var string PRODUCT_FILE_ENABLED
     */
    const PRODUCT_FILE_ENABLED = 'akeneo_connector/product/file_enabled';
    /**
     * File import attribute mapping config path
     *
     * @var string PRODUCT_FILE_ATTRIBUTE
     */
    const PRODUCT_FILE_ATTRIBUTE = 'akeneo_connector/product/file_attribute';
    /**
     * Product metrics config path
     *
     * @var string PRODUCT_METRICS
     */
    const PRODUCT_METRICS = 'akeneo_connector/product/metrics';
    /**
     * Akeneo master of staging content flag config path
     *
     * @var string PRODUCT_AKENEO_MASTER
     */
    const PRODUCT_AKENEO_MASTER = 'akeneo_connector/product/akeneo_master';
    /**
     * Attribute types mapping config path
     *
     * @var string ATTRIBUTE_TYPES
     */
    const ATTRIBUTE_TYPES = 'akeneo_connector/attribute/types';
    /**
     * Attribute filter updated mode
     *
     * @var string ATTRIBUTE_FILTERS_UPDATED_MODE
     */
    const ATTRIBUTE_FILTERS_UPDATED_MODE = 'akeneo_connector/filter_attribute/updated_mode';
    /**
     * Attribute filter greater
     *
     * @var string ATTRIBUTE_FILTERS_UPDATED_GREATER
     */
    const ATTRIBUTE_FILTERS_UPDATED_GREATER = 'akeneo_connector/filter_attribute/updated_greater';
    /**
     * Attribute filter by code mode
     *
     * @var string ATTRIBUTE_FILTERS_BY_CODE_MODE
     */
    const ATTRIBUTE_FILTERS_BY_CODE_MODE = 'akeneo_connector/filter_attribute/filter_attribute_code_mode';
    /**
     * Attribute filter by code
     *
     * @var string ATTRIBUTE_FILTERS_BY_CODE
     */
    const ATTRIBUTE_FILTERS_BY_CODE = 'akeneo_connector/filter_attribute/filter_attribute_code';
    /**
     * Akeneo master of staging content flag config path
     *
     * @var string PRODUCT_ASSOCIATION_RELATED
     */
    const PRODUCT_ASSOCIATION_RELATED = 'akeneo_connector/product/association_related';
    /**
     * Akeneo master of staging content flag config path
     *
     * @var string PRODUCT_ASSOCIATION_UPSELL
     */
    const PRODUCT_ASSOCIATION_UPSELL = 'akeneo_connector/product/association_upsell';
    /**
     * Akeneo master of staging content flag config path
     *
     * @var string PRODUCT_AKENEO_MASTER
     */
    const PRODUCT_ASSOCIATION_CROSSELL = 'akeneo_connector/product/association_crossell';
    /**
     * Product activation flag config path
     *
     * @var string PRODUCT_ACTIVATION
     */
    const PRODUCT_ACTIVATION = 'akeneo_connector/product/activation';
    /**
     * Grouped product families mapping path
     *
     * @var string GROUPED_PRODUCTS_FAMILIES_MAPPING
     */
    const GROUPED_PRODUCTS_FAMILIES_MAPPING = 'akeneo_connector/grouped_products/families_mapping';
    /**
     * @var int PAGINATION_SIZE_DEFAULT_VALUE
     */
    const PAGINATION_SIZE_DEFAULT_VALUE = 10;
    /**
     * Families filters updated mode config path
     *
     * @var string FAMILIES_FILTERS_UPDATED_MODE
     */
    const FAMILIES_FILTERS_UPDATED_MODE = 'akeneo_connector/families/updated_mode';
    /**
     * Families filters updated greater config path
     *
     * @var string FAMILIES_FILTERS_UPDATED_GREATER
     */
    const FAMILIES_FILTERS_UPDATED_GREATER = 'akeneo_connector/families/updated_greater';
    /**
     * Advanced logs activation config path
     *
     * @var string ADVANCED_LOG
     */
    const ADVANCED_LOG = 'akeneo_connector/advanced/advanced_log';
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
     * This variable contains reference records media directory
     *
     * @var string $recordMediaFile
     */
    protected $filesMediaFile = 'akeneo_connector/media_files';
    /**
     * Description $scopeConfig field
     *
     * @var ScopeConfigInterface $scopeConfig
     */
    protected $scopeConfig;

    /**
     * Config constructor
     *
     * @param Encryptor                     $encryptor
     * @param Serializer                    $serializer
     * @param EavConfig                     $eavConfig
     * @param StoreManagerInterface         $storeManager
     * @param CatalogInventoryConfiguration $catalogInventoryConfiguration
     * @param Filesystem                    $filesystem
     * @param MediaConfig                   $mediaConfig
     * @param ScopeConfigInterface          $scopeConfig
     *
     * @throws FileSystemException
     */
    public function __construct(
        Encryptor $encryptor,
        Serializer $serializer,
        EavConfig $eavConfig,
        StoreManagerInterface $storeManager,
        CatalogInventoryConfiguration $catalogInventoryConfiguration,
        Filesystem $filesystem,
        MediaConfig $mediaConfig,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->encryptor                     = $encryptor;
        $this->serializer                    = $serializer;
        $this->eavConfig                     = $eavConfig;
        $this->storeManager                  = $storeManager;
        $this->mediaConfig                   = $mediaConfig;
        $this->catalogInventoryConfiguration = $catalogInventoryConfiguration;
        $this->mediaDirectory                = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->scopeConfig                   = $scopeConfig;
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
     * @throws Exception
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
     * Check if all API credentials are correctly set
     *
     * @return bool
     */
    public function checkAkeneoApiCredentials()
    {
        if (!$this->getAkeneoApiBaseUrl() || !$this->getAkeneoApiClientId() || !$this->getAkeneoApiClientSecret(
            ) || !$this->getAkeneoApiPassword() || !$this->getAkeneoApiUsername()) {
            return false;
        }

        return true;
    }

    /**
     * Get pim edition
     *
     * @return string
     */
    public function getEdition()
    {
        return $this->scopeConfig->getValue(self::AKENEO_API_EDITION);
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
     * Retrieve the locales to apply the completeness filter on
     *
     * @return string
     */
    public function getModelCompletenessLocalesFilter()
    {
        return $this->scopeConfig->getValue(self::PRODUCTS_MODEL_FILTERS_COMPLETENESS_LOCALES);
    }

    /**
     * Retrieve the type of filter to apply on the completeness for product model
     *
     * @return string
     * @see \Akeneo\Connector\Model\Source\Filters\ModelCompleteness
     *
     */
    public function getModelCompletenessTypeFilter()
    {
        return $this->scopeConfig->getValue(self::PRODUCTS_MODEL_FILTERS_COMPLETENESS_TYPE);
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
     * Retrieve attribute updated mode
     *
     * @return string
     */
    public function getAttributeUpdatedMode()
    {
        return $this->scopeConfig->getValue(self::ATTRIBUTE_FILTERS_UPDATED_MODE);
    }

    /**
     * Retrieve the attribute updated after filter
     *
     * @return string
     */
    public function getAttributeUpdatedGreaterFilter()
    {
        return $this->scopeConfig->getValue(self::ATTRIBUTE_FILTERS_UPDATED_GREATER);
    }

    /**
     * Retrieve the attribute filter by code mode
     *
     * @return bool
     */
    public function getAttributeFilterByCodeMode()
    {
        return $this->scopeConfig->getValue(self::ATTRIBUTE_FILTERS_BY_CODE_MODE);
    }

    /**
     * Retrieve the attribute filter by code
     *
     * @return array
     */
    public function getAttributeFilterByCode()
    {
        return $this->scopeConfig->getValue(self::ATTRIBUTE_FILTERS_BY_CODE);
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
     * Retrieve the product model advance filters
     *
     * @return array
     */
    public function getModelAdvancedFilters()
    {
        $filters = $this->scopeConfig->getValue(self::PRODUCTS_MODEL_FILTERS_ADVANCED_FILTER);

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
     * @throws Exception
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
     * @throws Exception
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
    public function getPaginationSize()
    {
        /** @var string|int $paginationSize */
        $paginationSize = (int)$this->scopeConfig->getValue(self::AKENEO_API_PAGINATION_SIZE);
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
     * @throws LocalizedException
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
     * @throws LocalizedException
     */
    public function getAttribute($entityType, $code)
    {
        return $this->eavConfig->getAttribute($entityType, $code);
    }

    /**
     * Retrieve the status of newly imported products
     *
     * @return string
     */
    public function getProductActivation()
    {
        return $this->scopeConfig->getValue(self::PRODUCT_ACTIVATION);
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
     * @throws NoSuchEntityException
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
     * Description isAkeneoMaster function
     *
     * @return bool
     */
    public function isAkeneoMaster()
    {
        return $this->scopeConfig->isSetFlag(self::PRODUCT_AKENEO_MASTER);
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
     * Retrieve is file import is enabled
     *
     * @return bool
     */
    public function isFileImportEnabled()
    {
        return $this->scopeConfig->isSetFlag(self::PRODUCT_FILE_ENABLED);
    }

    /**
     * Retrieve file attribute columns
     *
     * @return array
     */
    public function getFileImportColumns()
    {
        /** @var array $fileAttributes */
        $fileAttributes = [];
        /** @var string $config */
        $config = $this->scopeConfig->getValue(self::PRODUCT_FILE_ATTRIBUTE);
        if (!$config) {
            return $fileAttributes;
        }

        /** @var array $media */
        $attributes = $this->serializer->unserialize($config);
        if (!$attributes) {
            return $fileAttributes;
        }

        foreach ($attributes as $attribute) {
            if (!isset($attribute['file_attribute']) || $attribute['file_attribute'] === '') {
                continue;
            }
            $fileAttributes[] = $attribute['file_attribute'];
        }

        return $fileAttributes;
    }

    /**
     * Retrieve media attribute column
     *
     * @return array
     */
    public function getMediaImportGalleryColumns($raw = false)
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
            if (!isset($image['attribute']) || $image['attribute'] === '') {
                continue;
            }

            if ($raw) {
                if(!isset($image['position'])) {
                    $image['position'] = 0;
                }
                $images[] = $image;
            } else {
                $images[] = $image['attribute'];
            }
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
     * @param string $filePath
     *
     * @return bool
     */
    public function mediaFileExists($filePath)
    {
        return $this->mediaDirectory->isFile($filePath);
    }

    /**
     * Get media full path
     *
     * @param string      $fileName
     * @param null|string $subDirectory
     *
     * @return string
     * @throws FileSystemException
     */
    public function getMediaFullPath($fileName, $subDirectory = null)
    {
        if ($subDirectory) {
            return $subDirectory . $this->getMediaFilePath($fileName);
        }

        return $this->mediaConfig->getMediaPath($this->getMediaFilePath($fileName));
    }

    /**
     * Download media by fullpath
     *
     * @param string $filename
     * @param string $content
     *
     * @return void
     */
    public function saveMediaFile($filePath, $content)
    {
        $this->mediaDirectory->writeFile($filePath, $content);
    }

    /**
     * Get Files Media Directory
     *
     * @return string
     */
    public function getFilesMediaDirectory()
    {
        return $this->filesMediaFile;
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
            $match            = array_map('strtolower', $match);
            $loweredMatches[] = $match;
        }

        return $loweredMatches;
    }

    /**
     * Get all grouped families from the mapping
     *
     * @return string[]
     */
    public function getGroupedFamiliesToImport()
    {
        /** @var string $familiesSerialized */
        $familiesSerialized = $this->scopeConfig->getValue(self::GROUPED_PRODUCTS_FAMILIES_MAPPING);
        /** @var mixed[] $associations */
        $associations = $this->serializer->unserialize($familiesSerialized);
        /** @var string[] $families */
        $families = [];
        /** @var mixed[] $association */
        foreach ($associations as $association) {
            $families[] = $association['akeneo_grouped_family_code'];
        }

        return $families;
    }

    /**
     * Get all families and their associations to import
     *
     * @return string[]
     */
    public function getGroupedAssociationsToImport()
    {
        /** @var string $associationsSerialized */
        $associationsSerialized = $this->scopeConfig->getValue(self::GROUPED_PRODUCTS_FAMILIES_MAPPING);
        /** @var string[] $associations */
        $associations = $this->serializer->unserialize($associationsSerialized);

        return $associations;
    }

    /**
     * Description getGroupedAssociationsForFamily function
     *
     * @param string $family
     *
     * @return mixed[]
     */
    public function getGroupedAssociationsForFamily(string $family)
    {
        /** @var string[] $allAssociations */
        $allAssociations = $this->getGroupedAssociationsToImport();
        /** @var mixed[] $associations */
        $associations = [];

        /** @var string[] $association */
        foreach ($allAssociations as $association) {
            if ($association['akeneo_grouped_family_code'] === $family) {
                $associations[] = $association;
            }
        }

        return $associations;
    }

    /**
     * Returns default attribute-set id for given entity
     *
     * @param string $entity
     *
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getDefaultAttributeSetId($entity)
    {
        return $this->eavConfig->getEntityType($entity)->getDefaultAttributeSetId();
    }

    /**
     * Description getFamiliesUpdatedMode function
     *
     * @return string|null
     */
    public function getFamiliesUpdatedMode()
    {
        return $this->scopeConfig->getValue(self::FAMILIES_FILTERS_UPDATED_MODE);
    }

    /**
     * Description getFamiliesUpdatedGreater function
     *
     * @return string|null
     */
    public function getFamiliesUpdatedGreater()
    {
        return $this->scopeConfig->getValue(self::FAMILIES_FILTERS_UPDATED_GREATER);
    }

    /**
     * Get association types configuration array for product import
     *
     * @return string[]
     */
    public function getAssociationTypes()
    {
        /** @var string $relatedCode */
        $relatedCode  = $this->scopeConfig->getValue(self::PRODUCT_ASSOCIATION_RELATED);
        /** @var string $upsellCode */
        $upsellCode   = $this->scopeConfig->getValue(self::PRODUCT_ASSOCIATION_UPSELL);
        /** @var string $crossellCode */
        $crossellCode = $this->scopeConfig->getValue(self::PRODUCT_ASSOCIATION_CROSSELL);
        /** @var string[] $associationTypes */
        $associationTypes = [];
        if ($relatedCode) {
            $associationTypes[Link::LINK_TYPE_RELATED] = [
                $relatedCode . '-products',
                $relatedCode . '-product_models',
            ];
        }
        if ($upsellCode) {
            $associationTypes[Link::LINK_TYPE_UPSELL] = [
                $upsellCode . '-products',
                $upsellCode . '-product_models',
            ];
        }
        if ($crossellCode) {
            $associationTypes[Link::LINK_TYPE_CROSSSELL] = [
                $crossellCode . '-products',
                $crossellCode . '-product_models',
            ];
        }

        return $associationTypes;
    }
    
    /**
     * Get if advanced logs is active
     *
     * @return string|null
     */
    public function isAdvancedLogActivated()
    {
        return $this->scopeConfig->getValue(self::ADVANCED_LOG);
    }
}
