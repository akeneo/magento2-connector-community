<?php

declare(strict_types=1);

namespace Akeneo\Connector\Model\Processor;

use Akeneo\Connector\Api\ProcessClassFactoryInterface;
use Magento\Framework\ObjectManagerInterface;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
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
     * Description create function
     *
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
