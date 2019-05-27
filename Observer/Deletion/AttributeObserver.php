<?php

namespace Akeneo\Connector\Observer\Deletion;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Akeneo\Connector\Helper\Import\Entities;
use Akeneo\Connector\Job\Attribute as ImportJob;

/**
 * Class AttributeObserver
 *
 * @category  Class
 * @package   Akeneo\Connector\Observer\Deletion
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class AttributeObserver implements ObserverInterface
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
     * AttributeObserver Constructor
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
        /** @var $attribute \Magento\Eav\Model\Entity\Attribute */
        $attribute = $observer->getEvent()->getAttribute();

        $this->entities->delete($this->job->getCode(), $attribute->getId());
    }
}
