<?php

namespace Akeneo\Connector\Api;

use Akeneo\Connector\Api\Data\JobInterface;

/**
 * Interface JobRepositoryInterface
 *
 * @category  Interface
 * @package   Akeneo\Connector\Api
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
interface JobRepositoryInterface
{
    /**
     * Retrieve a job by its id
     *
     * @param int $id
     *
     * @return \Akeneo\Connector\Api\Data\JobInterface
     */
    public function get($id);

    /**
     * Retrieve a job by its code
     *
     * @param string $code
     *
     * @return \Akeneo\Connector\Api\Data\JobInterface
     */
    public function getByCode($code);

    /**
     * Save job object
     *
     * @param \Akeneo\Connector\Api\Data\JobInterface $job
     *
     * @return $this
     */
    public function save(\Akeneo\Connector\Api\Data\JobInterface $job);

    /**
     * Delete a job object
     *
     * @param \Akeneo\Connector\Api\Data\JobInterface $job
     *
     * @return $this
     */
    public function delete(\Akeneo\Connector\Api\Data\JobInterface $job);
}
