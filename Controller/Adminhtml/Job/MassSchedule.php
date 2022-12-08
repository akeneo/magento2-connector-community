<?php

declare(strict_types=1);

namespace Akeneo\Connector\Controller\Adminhtml\Job;

use Akeneo\Connector\Api\Data\JobInterface;
use Akeneo\Connector\Executor\JobExecutor;
use Akeneo\Connector\Helper\Config;
use Akeneo\Connector\Model\ResourceModel\Job\Collection;
use Akeneo\Connector\Model\ResourceModel\Job\CollectionFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\AlreadyExistsException;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class MassSchedule extends Action
{
    /**
     * In
     */
    private const CONDITION_TYPE_IN = 'in';

    /**
     * Not In
     */
    private const CONDITION_TYPE_NIN = 'nin';

    /**
     * Description $collectionFactory field
     *
     * @var CollectionFactory $collectionFactory
     */
    protected $collectionFactory;
    /**
     * Description $jobExecutor field
     *
     * @var JobExecutor $jobExecutor
     */
    protected $jobExecutor;
    /**
     * This variable contains a Config
     *
     * @var Config $configHelper
     */
    protected $configHelper;

    /**
     * MassSchedule constructor
     *
     * @param Context           $context
     * @param CollectionFactory $collectionFactory
     * @param JobExecutor       $jobExecutor
     * @param Config            $configHelper
     */
    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        JobExecutor $jobExecutor,
        Config $configHelper
    ) {
        parent::__construct($context);

        $this->collectionFactory = $collectionFactory;
        $this->jobExecutor       = $jobExecutor;
        $this->configHelper      = $configHelper;
    }

    /**
     * Description execute function
     *
     * @return Redirect
     * @throws AlreadyExistsException
     */
    public function execute()
    {
        /** @var int[] $ids */
        $ids = $this->getRequest()->getParam('selected') ?? []; // Mass action from checkbox
        $ids[] = $this->getRequest()->getParam('entity_id'); // Mass action from Action column
        $condition = self::CONDITION_TYPE_IN;

        if (!!$this->getRequest()->getParam('excluded')) {
            $ids = $this->getRequest()->getParam('excluded');
            $condition = self::CONDITION_TYPE_NIN;
        }

        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(JobInterface::ENTITY_ID, [$condition => $ids]);

        $message = ($this->configHelper->getEnableJobGridAutoReload())
            ? 'Job %1 correctly scheduled.'
            : 'Job %1 correctly scheduled. Please refresh the page in a few minutes to check the progress.'
        ;

        /** @var JobInterface $job */
        foreach ($collection->getItems() as $job) {
            if ($this->jobExecutor->checkStatusConditions($job, true)) {
                $this->jobExecutor->setJobStatus(JobInterface::JOB_SCHEDULED, $job);
                $this->messageManager->addSuccessMessage(
                    __($message, $job->getName())
                );
            }
        }
        if ($this->configHelper->getJobReportEnabled()) {
            $this->messageManager->addSuccessMessage(
                __('You will receive an email when the job is completed.')
            );
        }

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('*/*/');

        return $resultRedirect;
    }

    /**
     * Description _isAllowed function
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Akeneo_Connector::akeneo_connector_job');
    }
}
