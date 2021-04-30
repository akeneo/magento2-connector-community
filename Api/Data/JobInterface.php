<?php

declare(strict_types=1);

namespace Akeneo\Connector\Api\Data;

/**
 * Interface JobInterface
 *
 * @package   Akeneo\Connector\Api\Data
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
interface JobInterface
{
    /**
     * Constants for keys of data array.
     */
    const ENTITY_ID = 'entity_id';
    const CODE = 'code';
    const STATUS = 'status';
    const SCHEDULED_AT = 'scheduled_at';
    const LAST_EXECUTED_DATE = 'last_executed_date';
    const LAST_SUCCESS_DATE = 'last_success_date';
    const ORDER = 'order';
}
