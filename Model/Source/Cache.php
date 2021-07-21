<?php

namespace Akeneo\Connector\Model\Source;

use Magento\Framework\App\Cache\Type\Block;
use Magento\Framework\App\Cache\Type\Collection;
use Magento\Framework\App\Cache\Type\Config;
use Magento\Framework\App\Cache\Type\Layout;
use Magento\Framework\App\Cache\Type\Reflection;
use Magento\Framework\App\Cache\Type\Translate;
use Magento\Framework\Option\ArrayInterface;
use Magento\PageCache\Model\Cache\Type;

/**
 * Class Cache
 *
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Cache implements ArrayInterface
{
    /**
     * Return array of options for the cache
     *
     * @return array Format: array('<value>' => '<label>', ...)
     */
    public function toOptionArray()
    {
        return [
            [
                'label' => __('block_html'),
                'value' => Block::TYPE_IDENTIFIER,

            ],
            [
                'label' => __('full_page'),
                'value' => Type::TYPE_IDENTIFIER,
            ],
            [
                'label' => __('collections'),
                'value' => Collection::TYPE_IDENTIFIER,
            ],
            [
                'label' => __('config'),
                'value' => Config::TYPE_IDENTIFIER,
            ],
            [
                'label' => __('layout'),
                'value' => Layout::TYPE_IDENTIFIER,
            ],
            [
                'label' => __('reflection'),
                'value' => Reflection::TYPE_IDENTIFIER,
            ],
            [
                'label' => __('translate'),
                'value' => Translate::TYPE_IDENTIFIER,
            ],
        ];
    }
}
