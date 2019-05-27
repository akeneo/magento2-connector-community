<?php

namespace Akeneo\Connector\Job;

use Magento\Framework\Data\Collection;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Data\CollectionFactory as CollectionFactory;
use Akeneo\Connector\Api\Data\ImportInterface;
use Akeneo\Connector\Api\ImportRepositoryInterface;
use Akeneo\Connector\Helper\Config as ConfigHelper;

/**
 * Class ImportRepository
 *
 * @category  Class
 * @package   Akeneo\Connector\Job
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class ImportRepository implements ImportRepositoryInterface
{

    /**
     * This variable contains an EntityFactoryInterface
     *
     * @var EntityFactoryInterface $entityFactory
     */
    private $entityFactory;
    /**
     * This variable contains a Collection
     *
     * @var Collection $collection
     */
    private $collection;
    /**
     * This variable contains a ConfigHelper
     *
     * @var ConfigHelper $configHelper
     */
    protected $configHelper;

    /**
     * ImportRepository constructor.
     *
     * @param EntityFactoryInterface $entityFactory
     * @param CollectionFactory $collectionFactory
     * @param ConfigHelper $configHelper
     * @param array $data
     *
     * @throws \Exception
     */
    public function __construct(
        EntityFactoryInterface $entityFactory,
        CollectionFactory $collectionFactory,
        ConfigHelper $configHelper,
        $data = []
    ) {
        $this->entityFactory = $entityFactory;
        $this->collection    = $collectionFactory->create();
        $this->configHelper  = $configHelper;

        $this->initCollection($data);
    }

    /**
     * Load available imports
     *
     * @param array $data
     *
     * @return void
     * @throws \Exception
     */
    private function initCollection($data)
    {
        foreach ($data as $id => $class) {
            if (!class_exists($class)) {
                continue;
            }

            /** @var Import $import */
            $import = $this->entityFactory->create($class);

            $import->setData('id', $id);
            $this->add($import);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function add(DataObject $import)
    {
        $this->collection->addItem($import);
    }

    /**
     * {@inheritdoc}
     */
    public function getByCode($code)
    {
        /** @var ImportInterface $import */
        $import = $this->collection->getItemById($code);

        return $import;
    }

    /**
     * {@inheritdoc}
     */
    public function getList()
    {
        return $this->collection;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteByCode($code)
    {
        $this->collection->removeItemByKey($code);
    }
}
