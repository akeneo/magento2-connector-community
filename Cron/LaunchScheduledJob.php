<?php

declare(strict_types=1);

namespace Akeneo\Connector\Cron;

use Akeneo\Connector\Api\Data\JobInterface;
use Akeneo\Connector\Executor\JobExecutor;
use Akeneo\Connector\Model\ResourceModel\Job\Collection;
use Akeneo\Connector\Model\ResourceModel\Job\CollectionFactory;
use Magento\Framework\Exception\AlreadyExistsException;

/**
 * Class LaunchScheduledJob
 *
 * @package   Akeneo\Connector
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class LaunchScheduledJob
{
    /**
     * Description $jobExecutor field
     *
     * @var JobExecutor $jobExecutor
     */
    protected $jobExecutor;
    /**
     * Description $collectionFactory field
     *
     * @var CollectionFactory $collectionFactory
     */
    protected $collectionFactory;

    /**
     * LaunchScheduledJob constructor
     *
     * @param JobExecutor       $jobExecutor
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(JobExecutor $jobExecutor, CollectionFactory $collectionFactory)
    {
        $this->jobExecutor       = $jobExecutor;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Description execute function
     *
     * @return void
     * @throws AlreadyExistsException
     */
    public function execute()
    {
        /** @var Collection $scheduledJobs */
        $scheduledJobs = $this->collectionFactory->create()->addFieldToFilter(
            JobInterface::STATUS,
            JobInterface::JOB_SCHEDULED
        )->addOrder(JobInterface::POSITION);

        /** @var array $codes */
        $codes = $scheduledJobs->addFieldToSelect('code')->toArray()['items'];

        if (count($codes)) {
            $this->jobExecutor->execute(implode(',', $codes[0] ?? []));
        }
    }
}
