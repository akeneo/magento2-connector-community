<?php

declare(strict_types=1);

namespace Akeneo\Connector\Model\Source\Filters;

use Akeneo\Connector\Helper\Authenticator;
use Akeneo\Connector\Helper\Config as ConfigHelper;
use Akeneo\Connector\Model\Source\Edition;
use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Akeneo\Pim\ApiClient\Search\SearchBuilderFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Option\ArrayInterface;
use Psr\Log\LoggerInterface as Logger;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Category implements ArrayInterface
{
    /**
     * Akeneo pim edition config path
     *
     * @var string AKENEO_PIM_EDITION_CONFIG_PATH
     */
    public const AKENEO_PIM_EDITION_CONFIG_PATH = 'akeneo_connector/akeneo_api/edition';
    /**
     * Versions for which "is_root" property exists on categories
     *
     * @var string[] IS_ROOT_ENABLED_VERSIONS
     */
    public const IS_ROOT_ENABLED_VERSIONS = [
        Edition::SERENITY,
        Edition::GROWTH,
        Edition::SEVEN
    ];
    /**
     * This variable is used for Akeneo Authenticator
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
     * Description $scopeConfig field
     *
     * @var ScopeConfigInterface $scopeConfig
     */
    private $scopeConfig;
    /**
     * Akeneo search builder factory
     *
     * @var SearchBuilderFactory $searchBuilderFactory
     */
    private $searchBuilderFactory;

    /**
     * Category constructor
     *
     * @param Authenticator        $akeneoAuthenticator
     * @param Logger               $logger
     * @param ConfigHelper         $configHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param SearchBuilderFactory $searchBuilderFactory
     */
    public function __construct(
        Authenticator $akeneoAuthenticator,
        Logger $logger,
        ConfigHelper $configHelper,
        ScopeConfigInterface $scopeConfig,
        SearchBuilderFactory $searchBuilderFactory
    ) {
        $this->akeneoAuthenticator  = $akeneoAuthenticator;
        $this->logger               = $logger;
        $this->configHelper         = $configHelper;
        $this->scopeConfig          = $scopeConfig;
        $this->searchBuilderFactory = $searchBuilderFactory;
    }

    /**
     * Initialize options
     *
     * @return ResourceCursorInterface|array
     */
    public function getCategories()
    {
        /** @var array $categories */
        $categories = [];

        try {
            /** @var AkeneoPimClientInterface $client */
            $client = $this->akeneoAuthenticator->getAkeneoApiClient();
            if (empty($client)) {
                return $categories;
            }
            /** @var string|int $paginationSize */
            $paginationSize = $this->configHelper->getPaginationSize();
            /** @var string $akeneoPimVersion */
            $akeneoPimVersion = $this->scopeConfig->getValue(self::AKENEO_PIM_EDITION_CONFIG_PATH);

            if (in_array($akeneoPimVersion, self::IS_ROOT_ENABLED_VERSIONS)) {
                /** @var mixed[][] $isRootFilter */
                $isRootFilter = $this->searchBuilderFactory->create()
                    ->addFilter('is_root', '=', true)
                    ->getFilters();
                /** @var ResourceCursorInterface $akeneoCategories */
                $akeneoCategories = $client->getCategoryApi()->all(
                    $paginationSize,
                    ['search' => $isRootFilter]
                );
                /** @var mixed[] $category */
                foreach ($akeneoCategories as $category) {
                    if (!isset($category['code'])) {
                        continue;
                    }
                    $categories[$category['code']] = $category['code'];
                }
            } else {
                /** @var ResourceCursorInterface $akeneoCategories */
                $akeneoCategories = $client->getCategoryApi()->all($paginationSize);
                /** @var mixed[] $category */
                foreach ($akeneoCategories as $category) {
                    if (!isset($category['code']) || isset($category['parent'])) {
                        continue;
                    }
                    $categories[$category['code']] = $category['code'];
                }
            }
        } catch (\Exception $exception) {
            $this->logger->warning($exception->getMessage());
        }

        return $categories;
    }

    /**
     * Retrieve options value and label in an array
     *
     * @return array
     */
    public function toOptionArray()
    {
        /** @var array $categories */
        $categories = $this->getCategories();
        /** @var array $optionArray */
        $optionArray = [];

        /**
         * @var int    $optionValue
         * @var string $optionLabel
         */
        foreach ($categories as $optionValue => $optionLabel) {
            $optionArray[] = [
                'value' => $optionValue,
                'label' => $optionLabel,
            ];
        }

        return $optionArray;
    }
}
