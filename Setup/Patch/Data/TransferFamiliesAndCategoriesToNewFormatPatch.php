<?php

declare(strict_types=1);

namespace Akeneo\Connector\Setup\Patch\Data;

use Akeneo\Connector\Helper\Authenticator;
use Akeneo\Connector\Helper\Config as ConfigHelper;
use Akeneo\Connector\Model\Source\Filters\Category as CategoryFilterSourceModel;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class TransferFamiliesAndCategoriesToNewFormatPatch implements DataPatchInterface
{
    /**
     * $resourceConfig field
     *
     * @var ConfigInterface $resourceConfig
     */
    private $resourceConfig;

    /**
     * Description $configHelper field
     *
     * @var ConfigHelper $configHelper
     */
    private $configHelper;

    /**
     * This variable contains an Authenticator
     *
     * @var Authenticator $authenticator
     */
    protected $authenticator;

    /**
     * Description $categoryFilterSourceModel field
     *
     * @var CategoryFilterSourceModel $categoryFilterSourceModel ;
     */
    protected $categoryFilterSourceModel;

    /**
     * This variable contains a Json
     *
     * @var Json $jsonSerializer
     */
    protected $jsonSerializer;

    /**
     * @var ModuleDataSetupInterface
     */
    private ModuleDataSetupInterface $moduleDataSetup;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ConfigInterface $resourceConfig,
        ConfigHelper $configHelper,
        Authenticator $authenticator,
        CategoryFilterSourceModel $categoryFilterSourceModel,
        Json $jsonSerializer,
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->resourceConfig = $resourceConfig;
        $this->configHelper = $configHelper;
        $this->authenticator = $authenticator;
        $this->categoryFilterSourceModel = $categoryFilterSourceModel;
        $this->jsonSerializer = $jsonSerializer;
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * @return void
     */
    public function apply()
    {
        $akeneoClient = $this->authenticator->getAkeneoApiClient();
        if (!$akeneoClient) {
            return;
        }

        // Create inverted Family entries
        $allFamilies = $invertedFamilies = $this->getFamilies();
        $familiesExcluded = $this->configHelper->getFamiliesExcludedFilter();

        if ($familiesExcluded) {
            $invertedFamilies = array_diff($allFamilies, explode(',', $familiesExcluded ?? ''));
        }

        $this->resourceConfig->saveConfig(
            ConfigHelper::PRODUCTS_FILTERS_INCLUDED_FAMILIES,
            implode(',',array_map('strval', $invertedFamilies))
        );

        // Create Category entries
        $excludedCategories = $invertedCategories = $this->configHelper->getCategoriesExcludedFilter();
        $allParentCategories = array_keys($this->categoryFilterSourceModel->getCategories());

        if ($excludedCategories) {
            $invertedCategories = array_diff($allParentCategories, explode(',', $excludedCategories ?? ''));
        }

        $this->resourceConfig->saveConfig(
            ConfigHelper::PRODUCTS_CATEGORY_INCLUDED_CATEGORIES,
            implode(',', array_map('strval', $invertedCategories ?? []))
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @param \Akeneo\Pim\ApiClient\AkeneoPimClientInterface|null $akeneoClient
     *
     * @return array
     */
    protected function getFamilies(): array
    {
        $allFamilies = [];

        $akeneoClient = $this->authenticator->getAkeneoApiClient();

        $apiFamilies = $akeneoClient->getFamilyApi()->all($this->configHelper->getPaginationSize());
        foreach ($apiFamilies as $family) {
            if (!isset($family['code'])) {
                continue;
            }
            $allFamilies[] = $family['code'];
        }

        return $allFamilies;
    }
}
