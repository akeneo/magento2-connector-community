<?php

namespace Akeneo\Connector\Observer\Deletion;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Akeneo\Connector\Helper\Import\Entities;
use Akeneo\Connector\Job\Category as ImportJob;

/**
 * Class CategoryObserver
 *
 * @category  Class
 * @package   Akeneo\Connector\Observer\Deletion
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class CategoryObserver implements ObserverInterface
{
    /**
     * This variable contains an Entities
     *
     * @var Entities $entities
     */
    protected $entities;
    /**
     * This variable contains an Attribute
     *
     * @var ImportJob $job
     */
    protected $job;

    /**
     * CategoryObserver Constructor
     *
     * @param Entities $entities
     * @param ImportJob $job
     */
    public function __construct(
        Entities $entities,
        ImportJob $job
    ) {
        $this->entities = $entities;
        $this->job      = $job;
    }
    /**
     * Remove entity relation
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var $category \Magento\Catalog\Model\Category */
        $category = $observer->getEvent()->getCategory();

        $this->entities->delete($this->job->getCode(), $category->getId());
    }
}
