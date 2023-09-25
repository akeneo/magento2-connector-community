<?php

declare(strict_types=1);

namespace Akeneo\Connector\Plugin;

use Akeneo\Connector\Job\Product;
use Exception;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category as CategoryResource;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\VisualMerchandiser\Model\Category\Builder;
use Magento\VisualMerchandiser\Model\ResourceModel\Rules\Collection as RulesCollection;
use Magento\VisualMerchandiser\Model\ResourceModel\Rules\CollectionFactory as RulesCollectionFactory;

/**
 * @author    Bartosz Kubicki
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class RebuildDynamicCategories
{
    private CategoryResource $categoryResource;
    private CategoryCollectionFactory $categoryCollectionFactory;
    private ModuleManager $moduleManager;

    public function __construct(
        CategoryResource $categoryResource,
        CategoryCollectionFactory $categoryCollectionFactory,
        ModuleManager $moduleManager
    ) {
        $this->categoryResource = $categoryResource;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->moduleManager = $moduleManager;
    }

    public function afterSetCategories(Product $subject, $result)
    {
        if ($this->moduleManager->isEnabled('Magento_VisualMerchandiser')) {
            $this->rebuildDynamicCategories($subject);
        }

        return $result;
    }

    private function rebuildDynamicCategories(Product $productImport): void
    {
        /** @var $rulesCollection RulesCollection */
        $rulesCollection = ObjectManager::getInstance()->get(RulesCollectionFactory::class)->create();
        $rulesCollection->addFieldToFilter('is_active', ['eq' => 1]);
        $categoriesIds = $rulesCollection->getColumnValues('category_id');

        $categoryCollection = $this->categoryCollectionFactory->create();
        $categoryCollection->addAttributeToSelect('*')
            ->addFieldToFilter($this->categoryResource->getEntityIdField(), ['in' => $categoriesIds]);

        foreach ($categoryCollection as $category) {
            ObjectManager::getInstance()->get(Builder::class)->rebuildCategory($category);
            $this->saveRebuiltCategory($productImport, $category);
        }
    }

    private function saveRebuiltCategory(Product $productImport, Category $category): void
    {
        try {
            $this->categoryResource->save($category);
        } catch (Exception $exception) {
            $productImport->setAdditionalMessage(
                __(
                    'Dynamic category %1 (ID: %2) couldn\'t be rebuilt correctly',
                    $category->getName(),
                    $category->getId()
                )
            );
        }
    }
}
