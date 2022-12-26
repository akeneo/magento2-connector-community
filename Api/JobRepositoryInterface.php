<?php

declare(strict_types=1);

namespace Akeneo\Connector\Api;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
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
}
