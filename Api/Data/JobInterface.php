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
     * Success status
     *
     * @var int JOB_SUCCESS
     */
    const JOB_SUCCESS = 1;
    /**
     * Error status
     *
     * @var int JOB_ERROR
     */
    const JOB_ERROR = 2;
    /**
     * Processing status
     *
     * @var int JOB_PROCESSING
     */
    const JOB_PROCESSING = 3;
    /**
     * Scheduled status
     *
     * @var int JOB_SCHEDULED
     */
    const JOB_SCHEDULED = 4;
    /**
     * Pending status
     *
     * @var int JOB_PENDING
     */
    const JOB_PENDING = 5;
    /**
     * Job entity id
     *
     * @var string ENTITY_ID
     */
    const ENTITY_ID = 'entity_id';
    /**
     * Job code
     *
     * @var string CODE
     */
    const CODE = 'code';
    /**
     * Job status
     *
     * @var string STATUS
     */
    const STATUS = 'status';
    /**
     * Scheduled run date
     *
     * @var string SCHEDULED_AT
     */
    const SCHEDULED_AT = 'scheduled_at';
    /**
     * Last executed date
     *
     * @var string LAST_EXECUTED_DATE
     */
    const LAST_EXECUTED_DATE = 'last_executed_date';
    /**
     * Last success date
     *
     * @var string LAST_SUCCESS_DATE
     */
    const LAST_SUCCESS_DATE = 'last_success_date';
    /**
     * Execution job position
     *
     * @var string POSITION
     */
    const POSITION = 'position';
    /**
     * Job PHP class
     *
     * @var string JOB_CLASS
     */
    const JOB_CLASS = 'job_class';
    /**
     * Job name
     *
     * @var string NAME
     */
    const NAME = 'name';
}
