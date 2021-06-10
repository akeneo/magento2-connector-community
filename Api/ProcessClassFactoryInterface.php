<?php

namespace Akeneo\Connector\Api;

/**
 * Interface ProcessClassFactoryInterface
 *
 * @author                 Mattheo Geoffray <mattheo.geoffray@dnd.fr>
 * @copyright              Copyright (c) 2016 Agence Dn'D
 * @license                http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link                   http://www.dnd.fr/
 */
interface ProcessClassFactoryInterface
{

    /**
     * @param string $type
     * @param array $arguments
     *
     * @return object
     */
    public function create($type, array $arguments = []);
}
