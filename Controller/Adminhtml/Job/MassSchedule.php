<?php

declare(strict_types=1);

namespace Akeneo\Connector\Controller\Adminhtml\Job;

use Akeneo\Connector\Executor\JobExecutor;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

/**
 * Class Launch
 *
 * @package   Akeneo\Connector\Controller\Adminhtml\Job
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class MassSchedule extends Action
{
    /**
     * Description $jobExecutor function
     *
     * @var JobExecutor $jobExecutor
     */
    protected $jobExecutor;

    /**
     * MassLaunch constructor
     *
     * @param \Magento\Backend\App\Action\Context    $context
     * @param \Akeneo\Connector\Executor\JobExecutor $jobExecutor
     */
    public function __construct(
        Context $context,
        JobExecutor $jobExecutor
    ) {
        parent::__construct($context);

        $this->jobExecutor = $jobExecutor;
    }

    /**
     * Description execute function
     *
     * @return void
     */
    public function execute()
    {
        /** @var int[] $ids */
        $ids = $this->getRequest()->getParam('entity_ids');
        $this->jobExecutor->scheduleJobs($ids);
    }

    /**
     * Description _isAllowed function
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Akeneo_Connector::job');
    }
}
