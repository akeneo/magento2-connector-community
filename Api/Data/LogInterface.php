<?php

declare(strict_types=1);

namespace Akeneo\Connector\Api\Data;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
interface LogInterface
{
    /**
     * Log id constant
     *
     * @var string LOG_ID
     */
    public const LOG_ID = 'log_id';
    /**
     * Identifier constant
     *
     * @var string IDENTIFIER
     */
    public const IDENTIFIER = 'identifier';
    /**
     * Code constant
     *
     * @var string CODE
     */
    public const CODE = 'code';
    /**
     * Name constant
     *
     * @var string NAME
     */
    public const NAME = 'name';
    /**
     * Status constant
     *
     * @var string STATUS
     */
    public const STATUS = 'status';
    /**
     * Created at constant
     *
     * @var string CREATED_AT
     */
    public const CREATED_AT = 'created_at';
    /**
     * Akeneo connector import log constant
     *
     * @var string AKENEO_CONNECTOR_IMPORT_LOG
     */
    public const AKENEO_CONNECTOR_IMPORT_LOG = 'akeneo_connector_import_log';
    /**
     * Akeneo connector import log step constant
     *
     * @var string AKENEO_CONNECTOR_IMPORT_LOG_STEP
     */
    public const AKENEO_CONNECTOR_IMPORT_LOG_STEP = 'akeneo_connector_import_log_step';

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId();

    /**
     * Get Identifier
     *
     * @return int|null
     */
    public function getIdentifier();

    /**
     * Get code
     *
     * @return string
     */
    public function getCode();

    /**
     * Get name
     *
     * @return string|null
     */
    public function getName();

    /**
     * Get status
     *
     * @return int
     */
    public function getStatus();

    /**
     * Get creation time
     *
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set ID
     *
     * @param int $id
     *
     * @return LogInterface
     */
    public function setId($id);

    /**
     * Set Identifier
     *
     * @param string $identifier
     *
     * @return LogInterface
     */
    public function setIdentifier($identifier);

    /**
     * Set code
     *
     * @param string $code
     *
     * @return LogInterface
     */
    public function setCode($code);

    /**
     * Set name
     *
     * @param string $name
     *
     * @return LogInterface
     */
    public function setName($name);

    /**
     * Set status
     *
     * @param string $status
     *
     * @return LogInterface
     */
    public function setStatus($status);

    /**
     * Set creation time
     *
     * @param string $createdAt
     *
     * @return LogInterface
     */
    public function setCreatedAt($createdAt);
}
