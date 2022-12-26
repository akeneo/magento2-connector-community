<?php

declare(strict_types=1);

namespace Akeneo\Connector\Model;

use Akeneo\Connector\Api\Data\JobInterface;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Phrase;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Job extends AbstractModel implements JobInterface, IdentityInterface
{
    /**
     * Import cache tag
     *
     * @var string CACHE_TAG
     */
    public const CACHE_TAG = 'akeneo_connector_job';
    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'akeneo_connector_job';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Akeneo\Connector\Model\ResourceModel\Job::class);
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
        return $this->getData(self::ENTITY_ID);
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
     * Get status
     *
     * @return string|null
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * Get scheduled at time
     *
     * @return string|null
     */
    public function getScheduledAt()
    {
        return $this->getData(self::SCHEDULED_AT);
    }

    /**
     * Get last exececute date
     *
     * @return string|null
     */
    public function getLastExecutedDate()
    {
        return $this->getData(self::LAST_EXECUTED_DATE);
    }

    /**
     * Get last success date
     *
     * @return string|null
     */
    public function getLastSuccessDate()
    {
        return $this->getData(self::LAST_SUCCESS_DATE);
    }

    /**
     * Get order
     *
     * @return string|null
     */
    public function getOrder()
    {
        return $this->getData(self::POSITION);
    }

    /**
     * Description getJobClass function
     *
     * @return string|null
     */
    public function getJobClass()
    {
        return $this->getData(self::JOB_CLASS);
    }
    
    /**
     * Description getName function
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->getData(self::NAME);
    }

    /**
     * Description getLastSuccessExecutedDate function
     *
     * @return string|null
     */
    public function getLastSuccessExecutedDate()
    {
        return $this->getData(self::LAST_SUCCESS_EXECUTED_DATE);
    }

    /**
     * Set ID
     *
     * @param int $id
     *
     * @return Job
     */
    public function setId($id)
    {
        return $this->setData(self::ENTITY_ID, $id);
    }

    /**
     * Set code
     *
     * @param string $code
     *
     * @return Job
     */
    public function setCode($code)
    {
        return $this->setData(self::CODE, $code);
    }

    /**
     * Set name
     *
     * @param string $date
     *
     * @return Job
     */
    public function setScheduledAt($date)
    {
        return $this->setData(self::SCHEDULED_AT, $date);
    }

    /**
     * Set status
     *
     * @param int $status
     *
     * @return Job
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * Set last executed date
     *
     * @param string $date
     *
     * @return Job
     */
    public function setLastExecutedDate($date)
    {
        return $this->setData(self::LAST_EXECUTED_DATE, $date);
    }

    /**
     * Set last executed date
     *
     * @param string $date
     *
     * @return Job
     */
    public function setLastSuccessDate($date)
    {
        return $this->setData(self::LAST_SUCCESS_DATE, $date);
    }

    /**
     * Set Order
     *
     * @param int $order
     *
     * @return Job
     */
    public function setPosition($order)
    {
        return $this->setData(self::POSITION, $order);
    }

    /**
     * Description setJobClass function
     *
     * @param string $class
     *
     * @return Job
     */
    public function setJobClass($class)
    {
        return $this->setData(self::JOB_CLASS, $class);
    }

    /**
     * Description setName function
     *
     * @param string $name
     *
     * @return Job
     */
    public function setName($name)
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * Description setLastSuccessExecutedDate function
     *
     * @param string $date
     *
     * @return Job
     */
    public function setLastSuccessExecutedDate($date)
    {
        return $this->setData(self::LAST_SUCCESS_EXECUTED_DATE, $date);
    }

    /**
     * Description getStatusLabel function
     *
     * @return Phrase|string
     */
    public function getStatusLabel()
    {
        /** @var string $status */
        $status = "";
        switch ($this->getStatus()) {
            case JobInterface::JOB_SUCCESS:
                $status = __('Success');
                break;
            case JobInterface::JOB_ERROR:
                $status = __('Error');
                break;
            case JobInterface::JOB_PROCESSING:
                $status = __('Processing');
                break;
            case JobInterface::JOB_PENDING:
                $status = __('Pending');
                break;
            case JobInterface::JOB_SCHEDULED:
                $status = __('Scheduled');
                break;
        }

        return $status;
    }
}
