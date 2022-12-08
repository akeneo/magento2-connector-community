<?php

declare(strict_types=1);

namespace Akeneo\Connector\Model\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Framework\App\Cache\TypeList;
use Magento\Framework\DataObject;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Cache implements ArrayInterface
{
    /**
     * @var TypeList $typeList
     */
    private $typeList;

    /**
     * Cache constructor
     *
     * @param TypeList $typeList
     */
    public function __construct(
        TypeList $typeList
    ) {
        $this->typeList = $typeList;
    }

    /**
     * Return array of options for the cache
     *
     * @return array Format: array('<value>' => '<label>', ...)
     */
    public function toOptionArray()
    {
        /** @var string[] $cacheOptions */
        $cacheOptions = [];
        /** @var mixed[] $cacheTypes */
        $cacheTypes = $this->typeList->getTypes();

        /** @var DataObject $cacheData */
        foreach ($cacheTypes as $cacheData) {
            $cacheOptions[] = [
                'label' => $cacheData->getCacheType(),
                'value' => $cacheData->getId(),
            ];
        }

        return $cacheOptions;
    }
}
