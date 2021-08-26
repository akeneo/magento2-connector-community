<?php

namespace Akeneo\Connector\Api;

/**
 * Interface ProcessClassFactoryInterface
 *
 * @package   Akeneo\Connector\Api
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
interface ProcessClassFactoryInterface
{
    /**
     * @param string $type
     * @param array  $arguments
     *
     * @return object
     */
    public function create($type, array $arguments = []);
}
