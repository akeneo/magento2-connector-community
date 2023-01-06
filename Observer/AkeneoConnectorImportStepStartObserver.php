<?php

namespace Akeneo\Connector\Observer;

use Akeneo\Connector\Api\Data\JobInterface;
use Akeneo\Connector\Executor\JobExecutor;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Akeneo\Connector\Api\Data\ImportInterface;
use Akeneo\Connector\Api\LogRepositoryInterface;
use Akeneo\Connector\Job\Import;
use Akeneo\Connector\Model\Log as LogModel;
use Akeneo\Connector\Model\LogFactory;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class AkeneoConnectorImportStepStartObserver implements ObserverInterface
{
    /**
     * This variable contains a LogFactory
     *
     * @var LogFactory $logFactory
     */
    protected $logFactory;
    /**
     * This variable contains a LogRepositoryInterface
     *
     * @var LogRepositoryInterface $logRepository
     */
    protected $logRepository;

    /**
     * AkeneoConnectorImportStepStartObserver constructor
     *
     * @param LogFactory             $logFactory
     * @param LogRepositoryInterface $logRepository
     */
    public function __construct(
        LogFactory $logFactory,
        LogRepositoryInterface $logRepository
    ) {
        $this->logFactory    = $logFactory;
        $this->logRepository = $logRepository;
    }

    /**
     * Log start of the step
     *
     * @param Observer $observer
     *
     * @return $this
     */
    public function execute(Observer $observer)
    {
        /** @var JobExecutor $executor */
        $executor = $observer->getEvent()->getImport();
        /** @var Import $currentJobClass */
        $currentJobClass = $executor->getCurrentJobClass();
        /** @var string $nameExtension */
        $nameExtension = $currentJobClass->getFamily() ? ' - ' . $currentJobClass->getFamily() : '';

        if ($executor->getStep() == 0) {
            /** @var LogModel $log */
            $log = $this->logFactory->create();
            $log->setIdentifier($executor->getIdentifier());
            $log->setCode($executor->getCurrentJob()->getCode());
            $log->setName($executor->getCurrentJobClass()->getName() . $nameExtension);
            $log->setStatus(JobInterface::JOB_PROCESSING); // processing
            $this->logRepository->save($log);
        } else {
            $log = $this->logRepository->getByIdentifier($executor->getIdentifier());
        }

        if ($log->hasData()) {
            $log->addStep(
                [
                    'log_id'     => $log->getId(),
                    'identifier' => $executor->getIdentifier(),
                    'number'     => $executor->getStep(),
                    'method'     => $executor->getMethod(),
                    'message'    => $executor->getComment(),
                ]
            );
        }

        return $this;
    }
}
