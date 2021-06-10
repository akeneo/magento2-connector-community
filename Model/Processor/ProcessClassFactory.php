<?php

namespace Akeneo\Connector\Model\Processor;

use Akeneo\Connector\Api\ProcessClassFactoryInterface;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class ProcessClassFactory
 *
 * @author                 Mattheo Geoffray <mattheo.geoffray@dnd.fr>
 * @copyright              Copyright (c) 2016 Agence Dn'D
 * @license                http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link                   http://www.dnd.fr/
 */
class ProcessClassFactory implements ProcessClassFactoryInterface
{
    /**
     * Description $objectManager field
     *
     * @var ObjectManagerInterface $objectManager
     */
    private $objectManager;

    /**
     * ProcessClassFactory constructor.
     *
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    /**
     * @param string  $type
     * @param mixed[] $arguments
     *
     * @return object
     */
    public function create($type, array $arguments = [])
    {
        return $this->objectManager->create($type, $arguments);
    }
}
