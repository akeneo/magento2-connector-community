<?php

declare(strict_types=1);

namespace Akeneo\Connector\Helper;

use Exception;
use Magento\Catalog\Helper\Product as ProductHelper;
use Magento\Catalog\Model\Product\Link;
use Magento\Catalog\Model\Product\Media\Config as MediaConfig;
use Magento\CatalogInventory\Model\Configuration as CatalogInventoryConfiguration;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Directory\Model\Currency;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\File\Uploader;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Config
{
    /**
     * API URL config path
     *
     * @var string AKENEO_API_BASE_URL
     */
    public const AKENEO_API_BASE_URL = 'akeneo_connector/akeneo_api/base_url';
    /**
     * API user name config path
     *
     * @var string AKENEO_API_USERNAME
     */
    public const AKENEO_API_USERNAME = 'akeneo_connector/akeneo_api/username';
    /**
     * API password config path
     *
     * @var string AKENEO_API_PASSWORD
     */
    public const AKENEO_API_PASSWORD = 'akeneo_connector/akeneo_api/password';
    /**
     * API client id config path
     *
     * @var string AKENEO_API_CLIENT_ID
     */
    public const AKENEO_API_CLIENT_ID = 'akeneo_connector/akeneo_api/client_id';
    /**
     * API secret key config path
     *
     * @var string AKENEO_API_CLIENT_SECRET
     */
    public const AKENEO_API_CLIENT_SECRET = 'akeneo_connector/akeneo_api/client_secret';
    /**
     * API edition config path
     *
     * @var string AKENEO_API_EDITION
     */
    public const AKENEO_API_EDITION = 'akeneo_connector/akeneo_api/edition';
    /**
     * API pagination size config path
     *
     * @var string AKENEO_API_PAGINATION_SIZE
     */
    public const AKENEO_API_PAGINATION_SIZE = 'akeneo_connector/akeneo_api/pagination_size';
    /**
     * API admin channel config path
     *
     * @var string AKENEO_API_ADMIN_CHANNEL
     */
    public const AKENEO_API_ADMIN_CHANNEL = 'akeneo_connector/akeneo_api/admin_channel';
    /**
     * API website mapping with channels config path
     *
     * @var string AKENEO_API_WEBSITE_MAPPING
     */
    public const AKENEO_API_WEBSITE_MAPPING = 'akeneo_connector/akeneo_api/website_mapping';
    /**
     * Product filters mode config path
     *
     * @var string PRODUCTS_FILTERS_MODE
     */
    public const PRODUCTS_FILTERS_MODE = 'akeneo_connector/products_filters/mode';
    /**
     * Product filters completeness type config path
     *
     * @var string PRODUCTS_FILTERS_COMPLETENESS_TYPE
     */
    public const PRODUCTS_FILTERS_COMPLETENESS_TYPE = 'akeneo_connector/products_filters/completeness_type';
    /**
     * Product filters completeness value config path
     *
     * @var string PRODUCTS_FILTERS_COMPLETENESS_VALUE
     */
    public const PRODUCTS_FILTERS_COMPLETENESS_VALUE = 'akeneo_connector/products_filters/completeness_value';
    /**
     * Product filters completeness locales config path
     *
     * @var string PRODUCTS_FILTERS_COMPLETENESS_LOCALES
     */
    public const PRODUCTS_FILTERS_COMPLETENESS_LOCALES = 'akeneo_connector/products_filters/completeness_locales';
    /**
     * Product model filters completeness locales config path
     *
     * @var string PRODUCTS_MODEL_FILTERS_COMPLETENESS_LOCALES
     */
    public const PRODUCTS_MODEL_FILTERS_COMPLETENESS_LOCALES = 'akeneo_connector/products_filters/model_completeness_locales';
    /**
     * Product model filters completeness type config path
     *
     * @var string PRODUCTS_MODEL_FILTERS_COMPLETENESS_TYPE
     */
    public const PRODUCTS_MODEL_FILTERS_COMPLETENESS_TYPE = 'akeneo_connector/products_filters/model_completeness_type';
    /**
     * Product filters status config path
     *
     * @var string PRODUCTS_FILTERS_STATUS
     */
    public const PRODUCTS_FILTERS_STATUS = 'akeneo_connector/products_filters/status';
    /**
     * Product filters families config path
     *
     * @var string PRODUCTS_FILTERS_FAMILIES
     */
    public const PRODUCTS_FILTERS_FAMILIES = 'akeneo_connector/products_filters/families';
    /**
     * Product filters families config path
     *
     * @var string PRODUCTS_FILTERS_INCLUDED_FAMILIES
     */
    public const PRODUCTS_FILTERS_INCLUDED_FAMILIES = 'akeneo_connector/products_filters/included_families';
    /**
     * Product filters updated mode config path
     *
     * @var string PRODUCTS_FILTERS_UPDATED_MODE
     */
    public const PRODUCTS_FILTERS_UPDATED_MODE = 'akeneo_connector/products_filters/updated_mode';
    /**
     * Product filters updated lower config pathL
     *
     * @var string PRODUCTS_FILTERS_UPDATED_LOWER
     */
    public const PRODUCTS_FILTERS_UPDATED_LOWER = 'akeneo_connector/products_filters/updated_lower';
    /**
     * Product filters updated greater config path
     *
     * @var string PRODUCTS_FILTERS_UPDATED_GREATER
     */
    public const PRODUCTS_FILTERS_UPDATED_GREATER = 'akeneo_connector/products_filters/updated_greater';
    /**
     * Product filters updated between config path
     *
     * @var string PRODUCTS_FILTERS_UPDATED_BETWEEN_AFTER
     */
    public const PRODUCTS_FILTERS_UPDATED_BETWEEN_AFTER = 'akeneo_connector/products_filters/updated_between_after';
    /**
     * Product filters updated between before config path
     *
     * @var string PRODUCTS_FILTERS_UPDATED_BETWEEN_BEFORE
     */
    public const PRODUCTS_FILTERS_UPDATED_BETWEEN_BEFORE = 'akeneo_connector/products_filters/updated_between_before';
    /**
     * Product filters updated since config path
     *
     * @var string PRODUCTS_FILTERS_UPDATED_SINCE
     */
    public const PRODUCTS_FILTERS_UPDATED_SINCE = 'akeneo_connector/products_filters/updated';
    /**
     * Product filters updated since last hours config path
     *
     * @var string PRODUCTS_FILTERS_UPDATED_SINCE_LAST_HOURS
     */
    public const PRODUCTS_FILTERS_UPDATED_SINCE_LAST_HOURS = 'akeneo_connector/products_filters/updated_since_last_hours';
    /**
     * Product advanced filters config path
     *
     * @var string PRODUCTS_FILTERS_ADVANCED_FILTER
     */
    public const PRODUCTS_FILTERS_ADVANCED_FILTER = 'akeneo_connector/products_filters/advanced_filter';
    /**
     * Product model advanced filters config path
     *
     * @var string PRODUCTS_MODEL_FILTERS_ADVANCED_FILTER
     */
    public const PRODUCTS_MODEL_FILTERS_ADVANCED_FILTER = 'akeneo_connector/products_filters/model_advanced_filter';
    /**
     * Product category is active config path
     *
     * @var string PRODUCTS_CATEGORY_IS_ACTIVE
     */
    public const PRODUCTS_CATEGORY_IS_ACTIVE = 'akeneo_connector/category/is_active';
    /**
     * Categories are included in menu config path
     *
     * @var string PRODUCTS_CATEGORY_INCLUDE_IN_MENU
     */
    public const PRODUCTS_CATEGORY_INCLUDE_IN_MENU = 'akeneo_connector/category/include_in_menu';
    /**
     * Categories are anchor config path
     *
     * @var string PRODUCTS_CATEGORY_IS_ANCHOR
     */
    public const PRODUCTS_CATEGORY_IS_ANCHOR = 'akeneo_connector/category/is_anchor';
    /**
     * Categories to not import config path
     *
     * @var string PRODUCTS_CATEGORY_CATEGORIES
     */
    public const PRODUCTS_CATEGORY_CATEGORIES = 'akeneo_connector/category/categories';
    /**
     * Categories to import config path
     *
     * @var string PRODUCTS_CATEGORY_INCLUDED_CATEGORIES
     */
    public const PRODUCTS_CATEGORY_INCLUDED_CATEGORIES = 'akeneo_connector/category/included_categories';
    /**
     * Categories does override content staging
     *
     * @var string PRODUCTS_CATEGORY_OVERRIDE_CONTENT_STAGING
     */
    public const PRODUCTS_CATEGORY_OVERRIDE_CONTENT_STAGING = 'akeneo_connector/category/override_content_staging';
    /**
     * Product visibility attribute option enabled path
     *
     * @var string PRODUCT_VISIBILITY_ENABLED
     */
    public const PRODUCT_VISIBILITY_ENABLED = 'akeneo_connector/product/visibility_enabled';
    /**
     * Product default visibility attribute path
     *
     * @var string PRODUCT_DEFAULT_VISIBILITY
     */
    public const PRODUCT_DEFAULT_VISIBILITY = 'akeneo_connector/product/default_visibility';
    /**
     * Simple product visibility path
     *
     * @var string PRODUCT_VISIBILITY_SIMPLE
     */
    public const PRODUCT_VISIBILITY_SIMPLE = 'akeneo_connector/product/visibility_simple';
    /**
     * Configuratble product visibility  path
     *
     * @var string PRODUCT_VISIBILITY_CONFIGURABLE
     */
    public const PRODUCT_VISIBILITY_CONFIGURABLE = 'akeneo_connector/product/visibility_configurable';
    /**
     * Attribute mapping config path
     *
     * @var string PRODUCT_ATTRIBUTE_MAPPING
     */
    public const PRODUCT_ATTRIBUTE_MAPPING = 'akeneo_connector/product/attribute_mapping';
    /**
     * Akeneo attribute code for Magento SKU
     */
    public const PRODUCT_AKENEO_ATTRIBUTE_CODE_FOR_SKU = 'akeneo_connector/product/akeneo_attribute_code_for_sku';
    /**
     * Website attribute config path
     *
     * @var string PRODUCT_WEBSITE_ATTRIBUTE
     */
    public const PRODUCT_WEBSITE_ATTRIBUTE = 'akeneo_connector/product/website_attribute';
    /**
     * Mapping attribute config path
     *
     * @var string PRODUCT_MAPPING_ATTRIBUTE
     */
    public const PRODUCT_MAPPING_ATTRIBUTE = 'akeneo_connector/product/product_mapping_attribute';
    /**
     * Configurable attribute mapping config path
     *
     * @var string PRODUCT_CONFIGURABLE_ATTRIBUTES
     */
    public const PRODUCT_CONFIGURABLE_ATTRIBUTES = 'akeneo_connector/product/configurable_attributes';
    /**
     * Product tax class config path
     *
     * @var string PRODUCT_TAX_CLASS
     */
    public const PRODUCT_TAX_CLASS = 'akeneo_connector/product/tax_class';
    /**
     * Product url generation flag config path
     *
     * @var string PRODUCT_URL_GENERATION_ENABLED
     */
    public const PRODUCT_URL_GENERATION_ENABLED = 'akeneo_connector/product/url_generation_enabled';
    /**
     * Media import enabled config path
     *
     * @var string PRODUCT_MEDIA_ENABLED
     */
    public const PRODUCT_MEDIA_ENABLED = 'akeneo_connector/product/media_enabled';
    /**
     * Media attributes config path
     *
     * @var string PRODUCT_MEDIA_IMAGES
     */
    public const PRODUCT_MEDIA_IMAGES = 'akeneo_connector/product/media_images';
    /**
     * Media special images mapping config path
     *
     * @var string PRODUCT_MEDIA_GALLERY
     */
    public const PRODUCT_MEDIA_GALLERY = 'akeneo_connector/product/media_gallery';
    /**
     * File import flag config path
     *
     * @var string PRODUCT_FILE_ENABLED
     */
    public const PRODUCT_FILE_ENABLED = 'akeneo_connector/product/file_enabled';
    /**
     * File import attribute mapping config path
     *
     * @var string PRODUCT_FILE_ATTRIBUTE
     */
    public const PRODUCT_FILE_ATTRIBUTE = 'akeneo_connector/product/file_attribute';
    /**
     * Product metrics config path
     *
     * @var string PRODUCT_METRICS
     */
    public const PRODUCT_METRICS = 'akeneo_connector/product/metrics';
    /**
     * Akeneo master of staging content flag config path
     *
     * @var string PRODUCT_AKENEO_MASTER
     */
    public const PRODUCT_AKENEO_MASTER = 'akeneo_connector/product/akeneo_master';
    /**
     * Akeneo master of staging content flag config path
     *
     * @var string PRODUCT_ASSOCIATION_RELATED
     */
    public const PRODUCT_ASSOCIATION_RELATED = 'akeneo_connector/product/association_related';
    /**
     * Akeneo master of staging content flag config path
     *
     * @var string PRODUCT_ASSOCIATION_UPSELL
     */
    public const PRODUCT_ASSOCIATION_UPSELL = 'akeneo_connector/product/association_upsell';
    /**
     * Akeneo master of staging content flag config path
     *
     * @var string PRODUCT_AKENEO_MASTER
     */
    public const PRODUCT_ASSOCIATION_CROSSELL = 'akeneo_connector/product/association_crossell';
    /**
     * Attribute types mapping config path
     *
     * @var string ATTRIBUTE_TYPES
     */
    public const ATTRIBUTE_TYPES = 'akeneo_connector/attribute/types';
    /**
     * Attribute swatch types mapping config path
     */
    public const ATTRIBUTE_SWATCH_TYPES = 'akeneo_connector/attribute/types_swatch';
    /**
     * Attribute option code as admin label config path
     *
     * @var string ATTRIBUTE_OPTION_CODE_AS_ADMIN_LABEL
     */
    public const ATTRIBUTE_OPTION_CODE_AS_ADMIN_LABEL = 'akeneo_connector/attribute/option_code_as_admin_label';
    /**
     * Attribute filter updated mode
     *
     * @var string ATTRIBUTE_FILTERS_UPDATED_MODE
     */
    public const ATTRIBUTE_FILTERS_UPDATED_MODE = 'akeneo_connector/filter_attribute/updated_mode';
    /**
     * Attribute filter greater
     *
     * @var string ATTRIBUTE_FILTERS_UPDATED_GREATER
     */
    public const ATTRIBUTE_FILTERS_UPDATED_GREATER = 'akeneo_connector/filter_attribute/updated_greater';
    /**
     * Attribute filter by code mode
     *
     * @var string ATTRIBUTE_FILTERS_BY_CODE_MODE
     */
    public const ATTRIBUTE_FILTERS_BY_CODE_MODE = 'akeneo_connector/filter_attribute/filter_attribute_code_mode';
    /**
     * Attribute filter by code
     *
     * @var string ATTRIBUTE_FILTERS_BY_CODE
     */
    public const ATTRIBUTE_FILTERS_BY_CODE = 'akeneo_connector/filter_attribute/filter_attribute_code';
    /**
     * Product activation flag config path
     *
     * @var string PRODUCT_ACTIVATION
     */
    public const PRODUCT_ACTIVATION = 'akeneo_connector/product/activation';
    /**
     * Product status mode config path
     *
     * @var string PRODUCT_STATUS_MODE
     */
    public const PRODUCT_STATUS_MODE = 'akeneo_connector/product/product_status_mode';
    /**
     * Attribute code for simple product statuses config path
     *
     * @var string ATTRIBUTE_CODE_FOR_SIMPLE_PRODUCT_STATUSES
     */
    public const ATTRIBUTE_CODE_FOR_SIMPLE_PRODUCT_STATUSES = 'akeneo_connector/product/attribute_code_for_simple_product_statuses';
    /**
     * Attribute code for configurable product statuses config path
     *
     * @var string ATTRIBUTE_CODE_FOR_CONFIGURABLE_PRODUCT_STATUSES
     */
    public const ATTRIBUTE_CODE_FOR_CONFIGURABLE_PRODUCT_STATUSES = 'akeneo_connector/product/attribute_code_for_configurable_product_statuses';
    /**
     * Enable simple products per website config path
     *
     * @var string ENABLE_SIMPLE_PRODUCTS_PER_WEBSITE
     */
    public const ENABLE_SIMPLE_PRODUCTS_PER_WEBSITE = 'akeneo_connector/product/enable_simple_products_per_website';
    /**
     * Default configurable product status config path
     *
     * @var string DEFAULT_CONFIGURABLE_PRODUCT_STATUS
     */
    public const DEFAULT_CONFIGURABLE_PRODUCT_STATUS = 'akeneo_connector/product/default_configurable_product_status';
    /**
     * Grouped product families mapping path
     *
     * @var string GROUPED_PRODUCTS_FAMILIES_MAPPING
     */
    public const GROUPED_PRODUCTS_FAMILIES_MAPPING = 'akeneo_connector/grouped_products/families_mapping';
    /**
     * @var int PAGINATION_SIZE_DEFAULT_VALUE
     */
    public const PAGINATION_SIZE_DEFAULT_VALUE = 10;
    /**
     * Families filters updated mode config path
     *
     * @var string FAMILIES_FILTERS_UPDATED_MODE
     */
    public const FAMILIES_FILTERS_UPDATED_MODE = 'akeneo_connector/families/updated_mode';
    /**
     * Families filters updated greater config path
     *
     * @var string FAMILIES_FILTERS_UPDATED_GREATER
     */
    public const FAMILIES_FILTERS_UPDATED_GREATER = 'akeneo_connector/families/updated_greater';
    /**
     * Advanced logs activation config path
     *
     * @var string ADVANCED_LOG
     */
    public const ADVANCED_LOG = 'akeneo_connector/advanced/advanced_log';
    /**
     * Clean logs config path
     *
     * @var string CLEAN_LOGS
     */
    public const CLEAN_LOGS = 'akeneo_connector/advanced/clean_logs';
    /**
     * Enable clean logs config path
     *
     * @var string ENABLE_CLEAN_LOGS
     */
    public const ENABLE_CLEAN_LOGS = 'akeneo_connector/advanced/enable_clean_logs';
    /**
     * Cache type category config path
     *
     * @var string CACHE_TYPE_CATEGORY
     */
    public const CACHE_TYPE_CATEGORY = 'akeneo_connector/cache/cache_type_category';
    /**
     * Cache type family config path
     *
     * @var string CACHE_TYPE_FAMILY
     */
    public const CACHE_TYPE_FAMILY = 'akeneo_connector/cache/cache_type_family';
    /**
     * Cache type attribute config path
     *
     * @var string CACHE_TYPE_ATTRIBUTE
     */
    public const CACHE_TYPE_ATTRIBUTE = 'akeneo_connector/cache/cache_type_attribute';
    /**
     * Cache type option config path
     *
     * @var string CACHE_TYPE_OPTION
     */
    public const CACHE_TYPE_OPTION = 'akeneo_connector/cache/cache_type_option';
    /**
     * Cache type product config path
     *
     * @var string CACHE_TYPE_PRODUCT
     */
    public const CACHE_TYPE_PRODUCT = 'akeneo_connector/cache/cache_type_product';
    /**
     * Index category config path
     *
     * @var string INDEX_CATEGORY
     */
    public const INDEX_CATEGORY = 'akeneo_connector/index/index_category';
    /**
     * Index family config path
     *
     * @var string INDEX_FAMILY
     */
    public const INDEX_FAMILY = 'akeneo_connector/index/index_family';
    /**
     * Index attribute config path
     *
     * @var string INDEX_ATTRIBUTE
     */
    public const INDEX_ATTRIBUTE = 'akeneo_connector/index/index_attribute';
    /**
     * Index option config path
     *
     * @var string INDEX_OPTION
     */
    public const INDEX_OPTION = 'akeneo_connector/index/index_option';
    /**
     * Index product config path
     *
     * @var string INDEX_PRODUCT
     */
    public const INDEX_PRODUCT = 'akeneo_connector/index/index_product';
    /**
     * Email job report enabled path
     *
     * @var string EMAIL_JOB_REPORT_ENABLED
     */
    public const EMAIL_JOB_REPORT_ENABLED = 'akeneo_connector/advanced/email_job_report_enabled';
    /**
     * Email job report recicipient path
     *
     * @var string EMAIL_JOB_REPORT_RECIPIENT
     */
    public const EMAIL_JOB_REPORT_RECIPIENT = 'akeneo_connector/advanced/email_job_report_recipient';
    /**
     * Enable job grid auto reload path
     *
     * @var string EMAIL_JOB_REPORT_RECIPIENT
     */
    public const ENABLE_JOB_GRID_AUTO_RELOAD = 'akeneo_connector/advanced/enable_job_grid_auto_reload';
    /**
     * Email name job report from
     *
     * @var string EMAIL_JOB_REPORT_FROM_NAME
     */
    public const EMAIL_JOB_REPORT_FROM_NAME = 'trans_email/ident_general/name';
    /**
     * Email job report from
     *
     * @var string EMAIL_JOB_REPORT_FROM
     */
    public const EMAIL_JOB_REPORT_FROM = 'trans_email/ident_general/email';
    /**
     * This variable contains a Encryptor
     *
     * @var Encryptor $encryptor
     */
    protected $encryptor;
    /**
     * This variable contains a Json
     *
     * @var Json $jsonSerializer
     */
    protected $jsonSerializer;
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
     * @param Encryptor $encryptor
     * @param Json $jsonSerializer
     * @param EavConfig $eavConfig
     * @param StoreManagerInterface $storeManager
     * @param CatalogInventoryConfiguration $catalogInventoryConfiguration
     * @param Filesystem $filesystem
     * @param MediaConfig $mediaConfig
     * @param ScopeConfigInterface $scopeConfig
     *
     * @throws FileSystemException
     */
    public function __construct(
        Encryptor $encryptor,
        Json $jsonSerializer,
        EavConfig $eavConfig,
        StoreManagerInterface $storeManager,
        CatalogInventoryConfiguration $catalogInventoryConfiguration,
        Filesystem $filesystem,
        MediaConfig $mediaConfig,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->encryptor = $encryptor;
        $this->jsonSerializer = $jsonSerializer;
        $this->eavConfig = $eavConfig;
        $this->storeManager = $storeManager;
        $this->mediaConfig = $mediaConfig;
        $this->catalogInventoryConfiguration = $catalogInventoryConfiguration;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->scopeConfig = $scopeConfig;
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
     * @throws Exception
     */
    public function getAkeneoApiClientSecret()
    {
        /** @var string $apiClientSecret */
        $apiClientSecret = $this->scopeConfig->getValue(self::AKENEO_API_CLIENT_SECRET);

        return $this->encryptor->decrypt($apiClientSecret);
    }

    /**
     * Check if all API credentials are correctly set
     *
     * @return bool
     * @throws Exception
     */
    public function checkAkeneoApiCredentials()
    {
        if (!$this->getAkeneoApiBaseUrl()
            || !$this->getAkeneoApiClientId()
            || !$this->getAkeneoApiClientSecret()
            || !$this->getAkeneoApiPassword()
            || !$this->getAkeneoApiUsername()
        ) {
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
     * Retrieve the updated since last hours filter
     *
     * @return string
     */
    public function getUpdatedSinceLastHoursFilter()
    {
        return $this->scopeConfig->getValue(self::PRODUCTS_FILTERS_UPDATED_SINCE_LAST_HOURS);
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
     * Retrieve the excluded families to filter the products on
     *
     * @return string
     */
    public function getFamiliesExcludedFilter()
    {
        return $this->scopeConfig->getValue(self::PRODUCTS_FILTERS_FAMILIES);
    }

    /**
     * Retrieve the included families to filter the products on
     *
     * @return string
     */
    public function getFamiliesFilter()
    {
        return $this->scopeConfig->getValue(self::PRODUCTS_FILTERS_INCLUDED_FAMILIES);
    }

    /**
     * Retrieve the advance filters
     *
     * @return array
     */
    public function getAdvancedFilters()
    {
        $filters = $this->scopeConfig->getValue(self::PRODUCTS_FILTERS_ADVANCED_FILTER);

        return !empty($filters) ? $this->jsonSerializer->unserialize($filters) : [];
    }

    /**
     * Retrieve the product model advance filters
     *
     * @return array
     */
    public function getModelAdvancedFilters()
    {
        $filters = $this->scopeConfig->getValue(self::PRODUCTS_MODEL_FILTERS_ADVANCED_FILTER);

        return !empty($filters) ? $this->jsonSerializer->unserialize($filters) : [];
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
    public function getCategoriesExcludedFilter()
    {
        return $this->scopeConfig->getValue(self::PRODUCTS_CATEGORY_CATEGORIES);
    }

    /**
     * Retrieve the categories to filter the category import
     *
     * @return string
     */
    public function getCategoriesFilter()
    {
        return $this->scopeConfig->getValue(self::PRODUCTS_CATEGORY_INCLUDED_CATEGORIES);
    }

    /**
     * Retrieve the categories does override content staging
     *
     * @return string
     */
    public function getCategoriesIsOverrideContentStaging()
    {
        return $this->scopeConfig->getValue(self::PRODUCTS_CATEGORY_OVERRIDE_CONTENT_STAGING);
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
     * Retrieve the Akeneo attribute code for Magento SKU
     */
    public function getAkeneoAttributeCodeForSku(): ?string
    {
        return $this->scopeConfig->getValue(self::PRODUCT_AKENEO_ATTRIBUTE_CODE_FOR_SKU);
    }

    /**
     * Retrieve the name of the website association attribute
     *
     * @return string
     */
    public function getWebsiteAttribute()
    {
        return $this->scopeConfig->getValue(self::PRODUCT_WEBSITE_ATTRIBUTE);
    }

    /**
     * Retrieve the Akeneo attribute code for product type mapping
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getMappingAttribute()
    {
        return $this->scopeConfig->getValue(self::PRODUCT_MAPPING_ATTRIBUTE);
    }

    /**
     * Retrieve website mapping
     *
     * @param bool $withDefault
     *
     * @return mixed[]
     * @throws Exception
     */
    public function getWebsiteMapping($withDefault = true)
    {
        /** @var mixed[] $mapping */
        $mapping = [];

        if ($withDefault === true) {
            /** @var string $adminChannel */
            $adminChannel = $this->getAdminDefaultChannel();
            if (empty($adminChannel)) {
                throw new Exception((string)__('No channel found for Admin website channel configuration.'));
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
        $websiteMapping = $this->jsonSerializer->unserialize($websiteMapping);
        if (empty($websiteMapping) || !is_array($websiteMapping)) {
            return $mapping;
        }

        $mapping = array_merge($mapping, $websiteMapping);

        foreach ($mapping as $map) {
            if (!isset($map['channel'], $map['website'])) {
                throw new Exception(
                    (string)__('The website mapping is misconfigured, please check the "Website Mapping" config field.')
                );
            }
        }

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
     * Description getProductStatusMode function
     *
     * @return string
     */
    public function getProductStatusMode()
    {
        return $this->scopeConfig->getValue(self::PRODUCT_STATUS_MODE);
    }

    /**
     * Description getAttributeCodeForSimpleProductStatuses function
     *
     * @return string
     */
    public function getAttributeCodeForSimpleProductStatuses()
    {
        return $this->scopeConfig->getValue(self::ATTRIBUTE_CODE_FOR_SIMPLE_PRODUCT_STATUSES);
    }

    /**
     * Description getAttributeCodeForConfigurableProductStatuses function
     *
     * @return string
     */
    public function getAttributeCodeForConfigurableProductStatuses()
    {
        return $this->scopeConfig->getValue(self::ATTRIBUTE_CODE_FOR_CONFIGURABLE_PRODUCT_STATUSES);
    }

    /**
     * Description getEnableSimpleProductsPerWebsite function
     *
     * @return string
     */
    public function getEnableSimpleProductsPerWebsite()
    {
        return $this->scopeConfig->getValue(self::ENABLE_SIMPLE_PRODUCTS_PER_WEBSITE);
    }

    /**
     * Description getDefaultConfigurableProductStatus function
     *
     * @return string
     */
    public function getDefaultConfigurableProductStatus()
    {
        return $this->scopeConfig->getValue(self::DEFAULT_CONFIGURABLE_PRODUCT_STATUS);
    }

    /**
     * Retrieve stores default tax class
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getProductTaxClasses()
    {
        /** @var mixed[] $stores */
        $stores = $this->storeManager->getStores(true);
        /** @var mixed[] $result */
        $result = [];

        /** @var string $classes */
        $classes = $this->scopeConfig->getValue(self::PRODUCT_TAX_CLASS);
        if (!$classes) {
            return $result;
        }

        /** @var string[] $classes */
        $classes = $this->jsonSerializer->unserialize($classes);
        if (!is_array($classes)) {
            return $result;
        }

        /** @var mixed[] $class */
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
        $images = [];
        $config = $this->scopeConfig->getValue(self::PRODUCT_MEDIA_IMAGES);
        if (!$config) {
            return $images;
        }

        $media = $this->jsonSerializer->unserialize($config);
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
        /** @var mixed[] $fileAttributes */
        $fileAttributes = [];
        /** @var string $config */
        $config = $this->scopeConfig->getValue(self::PRODUCT_FILE_ATTRIBUTE);
        if (!$config) {
            return $fileAttributes;
        }

        /** @var mixed[] $media */
        $attributes = $this->jsonSerializer->unserialize($config);
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
    public function getMediaImportGalleryColumns()
    {
        /** @var mixed[] $images */
        $images = [];
        /** @var string $config */
        $config = $this->scopeConfig->getValue(self::PRODUCT_MEDIA_GALLERY);
        if (!$config) {
            return $images;
        }

        /** @var mixed[] $media */
        $media = $this->jsonSerializer->unserialize($config);
        if (!$media) {
            return $images;
        }

        foreach ($media as $image) {
            if (!isset($image['attribute']) || $image['attribute'] === '') {
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
        /** @var mixed[] $metrics */
        $metrics = [];
        /** @var string $config */
        $config = $this->scopeConfig->getValue(self::PRODUCT_METRICS);
        if (!$config) {
            return $metrics;
        }

        /** @var mixed[] $unserializeMetrics */
        $unserializeMetrics = $this->jsonSerializer->unserialize($config);
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
     * @param string $fileName
     * @param null|string $subDirectory
     *
     * @return string
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
     * @param string $filePath
     * @param string $content
     *
     * @return void
     * @throws FileSystemException
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
        /** @var mixed[] $matches */
        $matches = $this->jsonSerializer->unserialize($matches);
        /** @var mixed $loweredMatchs */
        $loweredMatches = [];
        /** @var string[] $match */
        foreach ($matches as $match) {
            $match            = array_map('strtolower', $match);
            $loweredMatches[] = $match;
        }

        return $loweredMatches;
    }

    public function isProductVisibilityEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::PRODUCT_VISIBILITY_ENABLED);
    }

    public function getProductDefaultVisibility(): string
    {
        return (string)$this->scopeConfig->getValue(self::PRODUCT_DEFAULT_VISIBILITY);
    }

    public function getProductVisibilitySimple(): string
    {
        return (string)$this->scopeConfig->getValue(self::PRODUCT_VISIBILITY_SIMPLE);
    }

    public function getProductVisibilityConfigurable(): string
    {
        return (string)$this->scopeConfig->getValue(self::PRODUCT_VISIBILITY_CONFIGURABLE);
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
        $associations = $this->jsonSerializer->unserialize($familiesSerialized);
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
        $associations = $this->jsonSerializer->unserialize($associationsSerialized);

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
     * @throws LocalizedException
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
        $relatedCode = $this->scopeConfig->getValue(self::PRODUCT_ASSOCIATION_RELATED);
        /** @var string $upsellCode */
        $upsellCode = $this->scopeConfig->getValue(self::PRODUCT_ASSOCIATION_UPSELL);
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

    /**
     * Description getCleanLogs function
     *
     * @return string|null
     */
    public function getCleanLogs()
    {
        return $this->scopeConfig->getValue(self::CLEAN_LOGS);
    }

    /**
     * Description getEnableCleanLogs function
     *
     * @return string|null
     */
    public function getEnableCleanLogs()
    {
        return $this->scopeConfig->getValue(self::ENABLE_CLEAN_LOGS);
    }

    /**
     * Description getOptionCodeAsAdminLabel function
     *
     * @return bool
     */
    public function getOptionCodeAsAdminLabel()
    {
        return $this->scopeConfig->getValue(self::ATTRIBUTE_OPTION_CODE_AS_ADMIN_LABEL);
    }

    /**
     * Get cache type attribute
     *
     * @return string|null
     */
    public function getCacheTypeAttribute()
    {
        return $this->scopeConfig->getValue(self::CACHE_TYPE_ATTRIBUTE);
    }

    /**
     * Get cache type category
     *
     * @return string|null
     */
    public function getCacheTypeCategory()
    {
        return $this->scopeConfig->getValue(self::CACHE_TYPE_CATEGORY);
    }

    /**
     * Get cache type family
     *
     * @return string|null
     */
    public function getCacheTypeFamily()
    {
        return $this->scopeConfig->getValue(self::CACHE_TYPE_FAMILY);
    }

    /**
     * Get cache type product
     *
     * @return string
     */
    public function getCacheTypeProduct()
    {
        return $this->scopeConfig->getValue(self::CACHE_TYPE_PRODUCT);
    }

    /**
     * Get cache type option
     *
     * @return string|null
     */
    public function getCacheTypeOption()
    {
        return $this->scopeConfig->getValue(self::CACHE_TYPE_OPTION);
    }

    /**
     * Get index category
     *
     * @return string|null
     */
    public function getIndexCategory()
    {
        return $this->scopeConfig->getValue(self::INDEX_CATEGORY);
    }

    /**
     * Get index attribute
     *
     * @return string|null
     */
    public function getIndexAttribute()
    {
        return $this->scopeConfig->getValue(self::INDEX_ATTRIBUTE);
    }

    /**
     * Get index family
     *
     * @return string|null
     */
    public function getIndexFamily()
    {
        return $this->scopeConfig->getValue(self::INDEX_FAMILY);
    }

    /**
     * Get index option
     *
     * @return string|null
     */
    public function getIndexOption()
    {
        return $this->scopeConfig->getValue(self::INDEX_OPTION);
    }

    /**
     * Get index product
     *
     * @return string|null
     */
    public function getIndexProduct()
    {
        return $this->scopeConfig->getValue(self::INDEX_PRODUCT);
    }

    /**
     * Description getJobReportEnabled function
     *
     * @return string|null
     */
    public function getJobReportEnabled()
    {
        return $this->scopeConfig->getValue(self::EMAIL_JOB_REPORT_ENABLED);
    }

    /**
     * Description getJobReportRecipient function
     *
     * @return string[]|null
     */
    public function getJobReportRecipient()
    {
        /** @var string $recipients */
        $recipients = $this->scopeConfig->getValue(self::EMAIL_JOB_REPORT_RECIPIENT);
        /** @var string[] $matches */
        $matches = [];
        preg_match_all(
            '/[a-zA-Z0-9\-.]*@[.a-zA-Z0-9\-]*/',
            $recipients,
            $matches
        );

        if (!$matches) {
            return null;
        }

        return $matches;
    }

    public function getEnableJobGridAutoReload(): bool
    {
        return $this->scopeConfig->isSetFlag(self::ENABLE_JOB_GRID_AUTO_RELOAD, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Description getStoreName function
     *
     * @return string|null
     */
    public function getStoreName()
    {
        return $this->scopeConfig->getValue(
            self::EMAIL_JOB_REPORT_FROM_NAME,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Description getStoreEmail function
     *
     * @return string|null
     */
    public function getStoreEmail()
    {
        return $this->scopeConfig->getValue(
            self::EMAIL_JOB_REPORT_FROM,
            ScopeInterface::SCOPE_STORE
        );
    }
}
