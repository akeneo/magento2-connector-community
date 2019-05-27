<?php

namespace Akeneo\Connector\Api;

use Akeneo\Connector\Api\Data\LogInterface;

/**
 * Interface LogRepositoryInterface
 *
 * @category  Interface
 * @package   Akeneo\Connector\Api
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
interface LogRepositoryInterface
{
    /**
     * Retrieve a log by its id
     *
     * @param int $id
     *
     * @return LogInterface
     */
    public function get($id);

    /**
     * Retrieve a log by its identifier
     *
     * @param string $identifier
     *
     * @return LogInterface
     */
    public function getByIdentifier($identifier);

    /**
     * Save log object
     *
     * @param LogInterface $log
     *
     * @return $this
     */
    public function save(LogInterface $log);

    /**
     * Delete a log object
     *
     * @param LogInterface $log
     *
     * @return $this
     */
    public function delete(LogInterface $log);
}
