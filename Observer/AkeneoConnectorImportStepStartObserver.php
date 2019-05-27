<?php

namespace Akeneo\Connector\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Akeneo\Connector\Api\Data\ImportInterface;
use Akeneo\Connector\Api\LogRepositoryInterface;
use Akeneo\Connector\Job\Import;
use Akeneo\Connector\Model\Log as LogModel;
use Akeneo\Connector\Model\LogFactory;

/**
 * Class AkeneoConnectorImportStepStartObserver
 *
 * @category  Class
 * @package   Akeneo\Connector\Observer
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
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
     * @param LogFactory $logFactory
     * @param LogRepositoryInterface $logRepository
     */
    public function __construct(
        LogFactory $logFactory,
        LogRepositoryInterface $logRepository
    ) {
        $this->logFactory = $logFactory;
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
        /** @var Import $import */
        $import = $observer->getEvent()->getImport();

        if ($import->getStep() == 0) {
            /** @var LogModel $log */
            $log = $this->logFactory->create();
            $log->setIdentifier($import->getIdentifier());
            $log->setCode($import->getCode());
            $log->setName($import->getName());
            $log->setStatus(ImportInterface::IMPORT_PROCESSING); // processing
            $this->logRepository->save($log);
        } else {
            $log = $this->logRepository->getByIdentifier($import->getIdentifier());
        }

        if ($log->hasData()) {
            $log->addStep(
                [
                    'log_id' => $log->getId(),
                    'identifier' => $import->getIdentifier(),
                    'number' => $import->getStep(),
                    'method' => $import->getMethod(),
                    'message' => $import->getComment(),
                ]
            );
        }

        return $this;
    }
}
