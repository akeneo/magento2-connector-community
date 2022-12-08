<?php

declare(strict_types=1);

namespace Akeneo\Connector\Model\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Indexer\Ui\DataProvider\Indexer\DataCollection;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Index implements ArrayInterface
{
    /**
     * @var DataCollection $dataCollection
     */
    private $dataCollection;

    /**
     * Index constructor
     *
     * @param DataCollection $dataCollection
     */
    public function __construct(
        DataCollection $dataCollection
    ) {
        $this->dataCollection = $dataCollection;
    }

    /**
     * Return array of options for the index
     *
     * @return array Format: array('<value>' => '<label>', ...)
     */
    public function toOptionArray()
    {
        /** @var string[] $indexerOptions */
        $indexerOptions = [];
        /** @var mixed[] $indexCollection */
        $indexCollection = $this->dataCollection->loadData();

        /** @var string[] $indexerData */
        foreach ($indexCollection as $indexerData) {
            $indexerOptions[] = [
                'label' => $indexerData['title'],
                'value' => $indexerData['indexer_id'],
            ];
        }

        return $indexerOptions;
    }
}
