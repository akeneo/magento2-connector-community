<?php

namespace Akeneo\Connector\Api\Data;

/**
 * Interface LogInterface
 *
 * @category  Interface
 * @package   Akeneo\Connector\Api\Data
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
interface LogInterface
{
    /**
     * Constants for keys of data array.
     */
    const LOG_ID     = 'log_id';
    const IDENTIFIER = 'identifier';
    const CODE       = 'code';
    const NAME       = 'name';
    const STATUS     = 'status';
    const CREATED_AT = 'created_at';

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
