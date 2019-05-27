<?php

namespace Akeneo\Connector\Model;

use Akeneo\Connector\Api\Data\LogInterface;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;

/**
 * Class Log
 *
 * @category  Class
 * @package   Akeneo\Connector\Model
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Log extends AbstractModel implements LogInterface, IdentityInterface
{
    /**
     * Import cache tag
     *
     * @var string CACHE_TAG
     */
    const CACHE_TAG = 'akeneo_connector_import_log';

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'akeneo_connector_import_log';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Akeneo\Connector\Model\ResourceModel\Log');
    }

    /**
     * Add step to log
     *
     * @param array $data
     *
     * @return $this
     * @throws \Exception
     */
    public function addStep(array $data)
    {
        $this->_getResource()->addStep($data);

        return $this;
    }

    /**
     * Retrieve steps
     *
     * @return array
     */
    public function getSteps()
    {
        return $this->_getResource()->getSteps($this->getId());
    }

    /**
     * Return unique ID(s) for each object in system
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->getData(self::LOG_ID);
    }

    /**
     * Get identifier
     *
     * @return int|null
     */
    public function getIdentifier()
    {
        return $this->getData(self::IDENTIFIER);
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->getData(self::CODE);
    }

    /**
     * Get name
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->getData(self::NAME);
    }

    /**
     * Get Status
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * Get creation time
     *
     * @return string|null
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * Set ID
     *
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        return $this->setData(self::LOG_ID, $id);
    }

    /**
     * Set Identifier
     *
     * @param string $identifier
     *
     * @return $this
     */
    public function setIdentifier($identifier)
    {
        return $this->setData(self::IDENTIFIER, $identifier);
    }

    /**
     * Set code
     *
     * @param string $code
     *
     * @return $this
     */
    public function setCode($code)
    {
        return $this->setData(self::CODE, $code);
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * Set status
     *
     * @param int $status
     *
     * @return $this
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * Set creation time
     *
     * @param string $createdAt
     *
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }
}
