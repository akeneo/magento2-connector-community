<?php

declare(strict_types=1);

namespace Akeneo\Connector\Api\Data;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
interface JobInterface
{
    /**
     * Success status
     *
     * @var int JOB_SUCCESS
     */
    public const JOB_SUCCESS = 1;
    /**
     * Error status
     *
     * @var int JOB_ERROR
     */
    public const JOB_ERROR = 2;
    /**
     * Processing status
     *
     * @var int JOB_PROCESSING
     */
    public const JOB_PROCESSING = 3;
    /**
     * Scheduled status
     *
     * @var int JOB_SCHEDULED
     */
    public const JOB_SCHEDULED = 4;
    /**
     * Pending status
     *
     * @var int JOB_PENDING
     */
    public const JOB_PENDING = 5;
    /**
     * Job entity id
     *
     * @var string ENTITY_ID
     */
    public const ENTITY_ID = 'entity_id';
    /**
     * Job code
     *
     * @var string CODE
     */
    public const CODE = 'code';
    /**
     * Job status
     *
     * @var string STATUS
     */
    public const STATUS = 'status';
    /**
     * Scheduled run date
     *
     * @var string SCHEDULED_AT
     */
    public const SCHEDULED_AT = 'scheduled_at';
    /**
     * Last executed date
     *
     * @var string LAST_EXECUTED_DATE
     */
    public const LAST_EXECUTED_DATE = 'last_executed_date';
    /**
     * Last success date
     *
     * @var string LAST_SUCCESS_DATE
     */
    public const LAST_SUCCESS_DATE = 'last_success_date';
    /**
     * The last executed date of the job which be successful
     *
     * @var string LAST_SUCCESS_EXECUTED_DATE
     */
    public const LAST_SUCCESS_EXECUTED_DATE = 'last_success_executed_date';
    /**
     * Execution job position
     *
     * @var string POSITION
     */
    public const POSITION = 'position';
    /**
     * Job PHP class
     *
     * @var string JOB_CLASS
     */
    public const JOB_CLASS = 'job_class';
    /**
     * Job name
     *
     * @var string NAME
     */
    public const NAME = 'name';
    /**
     * Default family code for product job
     *
     * @var string DEFAULT_PRODUCT_JOB_FAMILY_CODE
     */
    public const DEFAULT_PRODUCT_JOB_FAMILY_CODE = 'init_default_family_code';
}
