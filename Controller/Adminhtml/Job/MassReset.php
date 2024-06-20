<?php

declare(strict_types=1);

namespace Akeneo\Connector\Controller\Adminhtml\Job;

use Akeneo\Connector\Api\Data\JobInterface;
use Akeneo\Connector\Executor\JobExecutor;
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
class MassReset extends Action
{
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
     * MassReset constructor
     *
     * @param Context           $context
     * @param CollectionFactory $collectionFactory
     * @param JobExecutor       $jobExecutor
     */
    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        JobExecutor $jobExecutor
    ) {
        parent::__construct($context);

        $this->collectionFactory = $collectionFactory;
        $this->jobExecutor       = $jobExecutor;
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
        $ids = $this->getRequest()->getParam('selected');
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        if (!$this->getRequest()->getParam('excluded')) {
            $collection->addFieldToFilter(JobInterface::ENTITY_ID, ['in' => $ids]);
        }
        /** @var JobInterface $job */
        foreach ($collection->getItems() as $job) {
            $this->jobExecutor->setJobStatus(JobInterface::JOB_PENDING, $job);
        }

        $this->messageManager->addSuccessMessage(__('Job(s) successfully reset'));

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
