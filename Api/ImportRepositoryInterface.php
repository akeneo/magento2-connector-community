<?php

namespace Akeneo\Connector\Api;

use Magento\Framework\DataObject;
use Akeneo\Connector\Api\Data\ImportInterface;

/**
 * Interface ImportRepositoryInterface
 *
 * @category  Interface
 * @package   Akeneo\Connector\Api
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
interface ImportRepositoryInterface
{

    /**
     * Description add function
     *
     * @param DataObject $import
     *
     * @return void
     */
    public function add(DataObject $import);

    /**
     * Description getByCode function
     *
     * @param string $code
     *
     * @return ImportInterface
     */
    public function getByCode($code);

    /**
     * Description getList function
     *
     * @return Iterable
     */
    public function getList();

    /**
     * Description deleteByCode function
     *
     * @param string $code
     *
     * @return void
     */
    public function deleteByCode($code);
}
