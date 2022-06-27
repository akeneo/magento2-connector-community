<?php

namespace Akeneo\Connector\Observer;

use Akeneo\Connector\Api\Data\JobInterface;
use Akeneo\Connector\Api\JobExecutorInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Akeneo\Connector\Api\Data\ImportInterface;
use Akeneo\Connector\Api\Data\LogInterface;
use Akeneo\Connector\Api\LogRepositoryInterface;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class AkeneoConnectorImportStepFinishObserver implements ObserverInterface
{
    /**
     * This variable contains a LogRepositoryInterface
     *
     * @var LogRepositoryInterface $logRepository
     */
    protected $logRepository;

    /**
     * AkeneoConnectorImportStepFinishObserver constructor
     *
     * @param LogRepositoryInterface $logRepository
     */
    public function __construct(
        LogRepositoryInterface $logRepository
    ) {
        $this->logRepository = $logRepository;
    }

    /**
     * Log end of the step
     *
     * @param Observer $observer
     *
     * @return $this
     */
    public function execute(Observer $observer)
    {
        /** @var JobExecutorInterface $executor */
        $executor   = $observer->getEvent()->getImport();
        $currentJob = $executor->getCurrentJob();
        /** @var LogInterface $log */
        $log = $this->logRepository->getByIdentifier($executor->getIdentifier());

        if (!$log->hasData()) {
            return $this;
        }

        if ($executor->getStep() + 1 == $executor->countSteps() - 1
            || ($executor->isDone() && $currentJob->getStatus() != JobInterface::JOB_ERROR)
        ) {
            $log->setStatus(ImportInterface::IMPORT_SUCCESS); // Success
            $this->logRepository->save($log);
        }

        if ($executor->isDone() && $currentJob->getStatus() === JobInterface::JOB_ERROR) {
            $log->setStatus(ImportInterface::IMPORT_ERROR); // Error
            $this->logRepository->save($log);
        }

        $log->addStep(
            [
                'log_id'     => $log->getId(),
                'identifier' => $executor->getIdentifier(),
                'number'     => $executor->getStep(),
                'method'     => $executor->getMethod(),
                'message'    => $executor->getMessage(),
                'continue'   => $executor->isDone() ? 0 : 1,
                'status'     => $executor->getCurrentJobClass()->getStatus(),
            ]
        );

        return $this;
    }
}
