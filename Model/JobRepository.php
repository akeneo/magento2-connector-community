<?php

declare(strict_types=1);

namespace Akeneo\Connector\Model;

use Akeneo\Connector\Api\Data\JobInterface;
use Akeneo\Connector\Api\JobRepositoryInterface;
use Akeneo\Connector\Model\ResourceModel\Job as JobResourceModel;
use Akeneo\Connector\Model\ResourceModel\Job\Collection;
use Akeneo\Connector\Model\ResourceModel\Job\CollectionFactory;
use Exception;
use Magento\Framework\Exception\AlreadyExistsException;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class JobRepository implements JobRepositoryInterface
{
    /**
     * This variable contains a JobResourceModel
     *
     * @var JobResourceModel $jobResourceModel
     */
    protected $jobResourceModel;
    /**
     * This variable contains a JobFactory
     *
     * @var JobFactory $jobFactory
     */
    protected $jobFactory;
    /**
     * Description $collection field
     *
     * @var CollectionFactory $collectionFactory
     */
    protected $collectionFactory;

    /**
     * JobRepository constructor
     *
     * @param JobFactory        $jobFactory
     * @param JobResourceModel  $jobResourceModel
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        JobFactory $jobFactory,
        JobResourceModel $jobResourceModel,
        CollectionFactory $collectionFactory
    ) {
        $this->jobFactory        = $jobFactory;
        $this->jobResourceModel  = $jobResourceModel;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Description get function
     *
     * @param int $id
     *
     * @return JobInterface
     */
    public function get($id)
    {
        /** @var JobInterface $log */
        $job = $this->jobFactory->create();
        $this->jobResourceModel->load($job, $id);

        return $job;
    }

    /**
     * Description getByCode function
     *
     * @param string $code
     *
     * @return JobInterface
     */
    public function getByCode($code)
    {
        /** @var JobInterface $job */
        $job = $this->jobFactory->create();
        $this->jobResourceModel->load($job, $code, JobInterface::CODE);

        return $job;
    }

    /**
     * Description save function
     *
     * @param JobInterface $job
     *
     * @return JobRepository
     * @throws AlreadyExistsException
     */
    public function save(JobInterface $job)
    {
        $this->jobResourceModel->save($job);

        return $this;
    }

    /**
     * Description delete function
     *
     * @param JobInterface $job
     *
     * @return JobRepository
     * @throws Exception
     */
    public function delete(JobInterface $job)
    {
        $this->jobResourceModel->delete($job);

        return $this;
    }

    /**
     * Description getList function
     *
     * @return Collection
     */
    public function getList()
    {
        return $this->collectionFactory->create()->load();
    }
}
