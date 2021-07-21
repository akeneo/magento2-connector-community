<?php

namespace Akeneo\Connector\Model\Source;

use Magento\AdvancedSalesRule\Model\Indexer\SalesRule\Processor as SalesRuleProcessor;
use Magento\Catalog\Model\Indexer\Category\Product;
use Magento\Catalog\Model\Indexer\Product\Category;
use Magento\Catalog\Model\Indexer\Product\Eav\Processor;
use Magento\Catalog\Model\Indexer\Product\Price\Processor as PriceProcessor;
use Magento\CatalogInventory\Model\Indexer\Stock\Processor as StockProcessor;
use Magento\CatalogRule\Model\Indexer\Product\ProductRuleProcessor;
use Magento\CatalogRule\Model\Indexer\Rule\RuleProductProcessor;
use Magento\CatalogSearch\Model\Indexer\Fulltext;
use Magento\Customer\Model\Customer;
use Magento\Framework\Option\ArrayInterface;
use Magento\InventoryIndexer\Indexer\InventoryIndexer;
use Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor as RuleProcessor;
use Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Processor as ProductProcessor;
use Magento\Theme\Model\Data\Design\Config;

/**
 * Class Index
 *
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Index implements ArrayInterface
{
    /**
     * Return array of options for the index
     *
     * @return array Format: array('<value>' => '<label>', ...)
     */
    public function toOptionArray()
    {
        return [
            [
                'label' => __('design_config_grid'),
                'value' => Config::DESIGN_CONFIG_GRID_INDEXER_ID,
            ],
            [
                'label' => __('customer_grid'),
                'value' => Customer::CUSTOMER_GRID_INDEXER_ID,
            ],
            [
                'label' => __('catalog_category_product'),
                'value' => Product::INDEXER_ID,
            ],
            [
                'label' => __('catalog_product_category'),
                'value' => Category::INDEXER_ID,
            ],
            [
                'label' => __('catalogrule_rule'),
                'value' => RuleProductProcessor::INDEXER_ID,
            ],
            [
                'label' => __('catalog_product_attribute'),
                'value' => Processor::INDEXER_ID,
            ],
            [
                'label' => __('cataloginventory_stock'),
                'value' => StockProcessor::INDEXER_ID,
            ],
            [
                'label' => __('inventory'),
                'value' => InventoryIndexer::INDEXER_ID,
            ],
            [
                'label' => __('catalogrule_product'),
                'value' => ProductRuleProcessor::INDEXER_ID,
            ],
            [
                'label' => __('catalog_product_price'),
                'value' => PriceProcessor::INDEXER_ID,
            ],
            [
                'label' => __('catalogsearch_fulltext'),
                'value' => Fulltext::INDEXER_ID,
            ],
        ];
    }
}
