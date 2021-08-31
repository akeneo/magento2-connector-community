<?php

namespace Akeneo\Connector\Executor;

use Akeneo\Connector\Api\Data\JobInterface;
use Akeneo\Connector\Api\JobExecutorInterface;
use Akeneo\Connector\Helper\Authenticator;
use Akeneo\Connector\Helper\Config as ConfigHelper;
use Akeneo\Connector\Helper\Output as OutputHelper;
use Akeneo\Connector\Job\Import as JobImport;
use Akeneo\Connector\Model\Job;
use Akeneo\Connector\Model\JobRepository;
use Akeneo\Connector\Model\Processor\ProcessClassFactory;
use Akeneo\Connector\Model\ResourceModel\Job\Collection;
use Akeneo\Connector\Model\ResourceModel\Job\CollectionFactory;
use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Exception;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Phrase;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class JobExecutor
 *
 * @package   Akeneo\Connector\Executor
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class JobExecutor implements JobExecutorInterface
{
    /**
     * This constant contains a string
     *
     * @var string IMPORT_CODE_PRODUCT
     */
    const IMPORT_CODE_PRODUCT = 'product';
    /**
     * Description $jobRepository field
     *
     * @var JobRepository $jobRepository
     */
    protected $jobRepository;
    /**
     * Description $processClassFactory field
     *
     * @var ProcessClassFactory $processClassFactory
     */
    protected $processClassFactory;
    /**
     * This variable contains a ConfigHelper
     *
     * @var ConfigHelper $configHelper
     */
    protected $configHelper;
    /**
     * This variable contains an int value
     *
     * @var int $step
     */
    protected $step;
    /**
     * This variable contains an array
     *
     * @var array $steps
     */
    protected $steps;
    /**
     * Current executed Job object
     *
     * @var Job $currentJob
     */
    protected $currentJob;
    /**
     * Current executed Job object
     *
     * @var JobImport $currentJobClass
     */
    protected $currentJobClass;
    /**
     * This variable contains a bool value
     *
     * @var bool $setFromAdmin
     */
    protected $setFromAdmin;
    /**
     * This variable contains a AkeneoPimClientInterface
     *
     * @var AkeneoPimClientInterface $akeneoClient
     */
    protected $akeneoClient;
    /**
     * This variable contains a boolean
     *
     * @var bool $status
     */
    protected $status;
    /**
     * This variable contains an OutputHelper
     *
     * @var OutputHelper $outputHelper
     */
    protected $outputHelper;
    /**
     * This variable contains a mixed value
     *
     * @var ManagerInterface $eventManager
     */
    protected $eventManager;
    /**
     * This variable contains a bool value
     *
     * @var bool $continue
     */
    protected $continue;
    /**
     * This variable contains a string or Phrase value
     *
     * @var string|Phrase $message
     */
    protected $message;
    /**
     * This variable contains an Authenticator
     *
     * @var \Akeneo\Connector\Helper\Authenticator $authenticator
     */
    protected $authenticator;
    /**
     * Description $identifier field
     *
     * @var string $identifier
     */
    protected $identifier;
    /**
     * Description $output field
     *
     * @var OutputInterface|null $output
     */
    protected $output;
    /**
     * Description $jobCollectionFactory
     *
     * @var CollectionFactory
     */
    protected $jobCollectionFactory;

    /**
     * JobExecutor constructor
     *
     * @param JobRepository       $jobRepository
     * @param ProcessClassFactory $processClassFactory
     * @param ConfigHelper        $configHelper
     * @param OutputHelper        $outputHelper
     * @param ManagerInterface    $eventManager
     * @param Authenticator       $authenticator
     * @param CollectionFactory   $jobCollectionFactory
     */
    public function __construct(
        JobRepository $jobRepository,
        ProcessClassFactory $processClassFactory,
        ConfigHelper $configHelper,
        OutputHelper $outputHelper,
        ManagerInterface $eventManager,
        Authenticator $authenticator,
        CollectionFactory $jobCollectionFactory
    ) {
        $this->jobRepository        = $jobRepository;
        $this->processClassFactory  = $processClassFactory;
        $this->configHelper         = $configHelper;
        $this->outputHelper         = $outputHelper;
        $this->eventManager         = $eventManager;
        $this->authenticator        = $authenticator;
        $this->jobCollectionFactory = $jobCollectionFactory;
    }

    /**
     * Load steps
     *
     * @return void
     */
    public function initSteps()
    {
        /** @var array $steps */
        $steps = [];
        if ($this->currentJobClass->getData('steps')) {
            $steps = $this->currentJobClass->getData('steps');
        }

        $this->steps = array_merge(
            [
                [
                    'method'  => 'beforeImport',
                    'comment' => 'Start import',
                ],
            ],
            $steps,
            [
                [
                    'method'  => 'afterImport',
                    'comment' => 'Import complete',
                ],
            ]
        );
    }

    /**
     * Set current step index
     *
     * @param int $step
     *
     * @return JobExecutorInterface
     */
    public function setStep($step)
    {
        $this->step = $step;

        return $this;
    }

    /**
     * Get current step index
     *
     * @return int
     */
    public function getStep()
    {
        return $this->step;
    }

    /**
     * Description execute function
     *
     * @param string               $code
     * @param OutputInterface|null $output
     *
     * @return bool
     * @throws AlreadyExistsException
     */
    public function execute(string $code, ?OutputInterface $output = null)
    {
        if (!$this->configHelper->checkAkeneoApiCredentials()) {
            /** @var Phrase $message */
            $message = __('API credentials are missing. Please configure the connector and retry.');

            $this->displayError($message);

            return false;
        }

        /** @var string[] $entities */
        $entities = explode(',', $code);
        if (count($entities) > 1) {
            $entities = $this->sortJobs($entities);

            foreach ($entities as $code) {
                $this->execute($code, $output);
            }

            return true;
        }

        $this->output = $output;

        /** @var Job $job */
        $job = $this->jobRepository->getByCode($code);
        if (!$job) {
            /** @var Phrase $message */
            $message = __('Job code not found');

            $this->displayError($message);

            return false;
        }
        $this->currentJob      = $job;
        $this->currentJobClass = $this->processClassFactory->create($job->getJobClass());
        $this->currentJobClass->setJobExecutor($this);

        /** @var int $jobStatus */
        $jobStatus = $this->currentJob->getStatus();
        if ((int)$jobStatus === JobInterface::JOB_SCHEDULED && $output) {
            $this->displayError(__('The job %1 is already scheduled', [$this->currentJob->getCode()]));

            return false;
        }

        if ((int)$jobStatus === JobInterface::JOB_PROCESSING) {
            $this->displayError(__('The job %1 is already running', [$this->currentJob->getCode()]));

            return false;
        }

        // If product import, run the import once per family
        if ($code == self::IMPORT_CODE_PRODUCT) {
            /** @var array $productFamiliesToImport */
            $productFamiliesToImport = $this->currentJobClass->getFamiliesToImport();

            if (!count($productFamiliesToImport)) {
                $message = __('No family to import');
                $this->displayError($message);

                return false;
            }

            $this->beforeRun();
            foreach ($productFamiliesToImport as $family) {
                $this->run($family);
                $this->setIdentifier(null);
            }
            $this->afterRun();

            return true;
        }

        // Run the import normally
        $this->beforeRun();
        $this->run();
        $this->afterRun();

        return true;
    }

    /**
     * Description scheduleJobs function
     *
     * @param int[] $ids
     *
     * @return void
     */
    public function resetStatus($ids)
    {
        /** @var Collection $collection */
        $collection = $this->jobCollectionFactory->create()->addFieldToFilter(JobInterface::ENTITY_ID, ['in' => $ids]);
        /** @var JobInterface $job */
        foreach ($collection->getItems() as $job) {
            $this->setJobStatus(JobInterface::JOB_SCHEDULED, $job);
        }
    }

    protected function sortJobs($jobCodes)
    {
        /** @var Collection $collection */
        $collection = $this->jobCollectionFactory->create();

        $collection->addFieldToFilter(JobInterface::CODE, ['in' => $jobCodes]);
        $collection->addOrder(JobInterface::POSITION, 'ASC');
        $items = $collection->getItems();

        $sortedCodes = [];

        /** @var Job $item */
        foreach ($items as $item) {
            $sortedCodes[] = $item->getCode();
        }

        return $sortedCodes;
    }

    /**
     * Run the import
     *
     * @param null $family
     *
     * @return bool
     * @throws AlreadyExistsException
     */
    protected function run($family = null)
    {
        try {
            $this->initSteps();
            $this->setStep(0);
            if ($family) {
                $this->currentJobClass->setFamily($family);
            }

            while ($this->canExecute() && $this->currentJob->getStatus() !== JobInterface::JOB_ERROR) {
                /** @var string $comment */
                $comment = $this->getComment();
                $this->displayInfo($comment);

                $this->executeStep();

                /** @var string $message */
                $message = $this->getMessage();
                if ($this->currentJob->getStatus() == JobInterface::JOB_ERROR) {
                    $this->displayError($message);
                } else {
                    $this->displayComment($message);
                }
            }
        } catch (Exception $exception) {
            $this->afterRun(true);
        }

        return true;
    }

    /**
     * Count steps
     *
     * @return int
     */
    public function countSteps()
    {
        return count($this->steps);
    }

    /**
     * Description getCurrentJob function
     *
     * @return JobInterface
     */
    public function getCurrentJob()
    {
        return $this->currentJob;
    }

    /**
     * Description getCurrentJobClass function
     *
     * @return JobImport
     */
    public function getCurrentJobClass()
    {
        return $this->currentJobClass;
    }

    /**
     * Check if import may be processed (Not already running, ...)
     *
     * @return bool
     */
    public function canExecute()
    {
        if ($this->step < 0 || $this->step > $this->countSteps() - 1) {
            return false;
        }

        return true;
    }

    /**
     * Function called to run import
     * This function will get the right method to call
     *
     * @return void
     */
    public function executeStep()
    {
        if (!$this->canExecute() || !isset($this->steps[$this->step])) {
            return $this->outputHelper->getImportAlreadyRunningResponse();
        }
        /** @var string $method */
        $method = $this->getMethod();
        if (!method_exists($this->currentJobClass, $method)) {
            $this->afterRun(true);

            return $this->outputHelper->getNoImportFoundResponse();
        }

        if (!$this->akeneoClient) {
            $this->akeneoClient = $this->getAkeneoClient();
        }

        if (!$this->akeneoClient) {
            return $this->outputHelper->getApiConnectionError();
        }

        $this->eventManager->dispatch(
            'akeneo_connector_import_step_start',
            ['executor' => $this]
        );
        $this->eventManager->dispatch(
            'akeneo_connector_import_step_start_' . strtolower($this->currentJob->getCode()),
            ['executor' => $this]
        );

        $this->initStatus();

        try {
            $this->currentJobClass->{$method}();
        } catch (Exception $exception) {
            $this->afterRun(true);
            $this->setMessage($exception->getMessage());
        }

        $this->eventManager->dispatch('akeneo_connector_import_step_finish', ['executor' => $this]);
        $this->eventManager->dispatch(
            'akeneo_connector_import_step_finish_' . strtolower($this->currentJob->getCode()),
            ['executor' => $this]
        );

        $this->nextStep();
    }

    /**
     * Get method to execute
     *
     * @return string
     */
    public function getMethod()
    {
        return isset($this->steps[$this->getStep()]['method']) ? $this->steps[$this->getStep()]['method'] : null;
    }

    /**
     * Increment the step
     *
     * @return JobExecutor
     */
    public function nextStep()
    {
        $this->step += 1;

        return $this;
    }

    /**
     * Get the prefixed comment
     *
     * @return string
     */
    public function getComment()
    {
        return isset($this->steps[$this->getStep()]['comment']) ? $this->outputHelper->getPrefix(
            ) . $this->steps[$this->getStep()]['comment'] : $this->outputHelper->getPrefix() . get_class(
                $this
            ) . '::' . $this->getMethod();
    }

    /**
     * Init status, continue and message
     *
     * @return void
     */
    public function initStatus()
    {
        $this->currentJobClass->setStatus(true);
        $this->setContinue(true);
        $this->setMessage(__('completed'));
    }

    /**
     * Set continue
     *
     * @param bool $continue
     *
     * @return JobExecutorInterface
     */
    public function setContinue($continue)
    {
        $this->continue = $continue;

        return $this;
    }

    /**
     * Get Akeneo Client instance
     *
     * @return AkeneoPimClientInterface|false
     */
    public function getAkeneoClient()
    {
        try {
            /** @var AkeneoPimClientInterface|false $akeneoClient */
            $akeneoClient = $this->authenticator->getAkeneoApiClient();
        } catch (Exception $e) {
            $akeneoClient = false;
        }

        return $akeneoClient;
    }

    /**
     * Set import message
     *
     * @param string|Phrase $message
     *
     * @return JobExecutor
     */
    public function setMessage($message, $logger = null)
    {
        $this->message = $message;
        if ($logger && $this->configHelper->isAdvancedLogActivated()) {
            $this->currentJobClass->getLogger()->addDebug($message);
        }

        return $this;
    }

    /**
     * Description setJobStatus function
     *
     * @param int      $status
     * @param Job|null $job
     *
     * @return void
     * @throws AlreadyExistsException
     */
    public function setJobStatus(int $status, $job = null)
    {
        if (!$job) {
            $job = $this->currentJob;
        }

        if ($status === JobInterface::JOB_SCHEDULED) {
            $job->setScheduledAt(date('y-m-d h:i:s'));
        }

        $job->setStatus($status);
        $this->jobRepository->save($job);
    }

    /**
     * Description beforeRun function
     *
     * @return void
     * @throws AlreadyExistsException
     */
    public function beforeRun()
    {
        $this->setIdentifier(null);
        $this->eventManager->dispatch(
            'akeneo_connector_import_start',
            ['import' => $this->currentJobClass, 'executor' => $this]
        );
        $this->currentJob->setLastExecutedDate(date('y-m-d h:i:s'));
        $this->setJobStatus(JobInterface::JOB_PROCESSING);
    }

    /**
     * Description afterRun function
     *
     * @param bool|null $error
     * @param bool|null $onlyStop ;
     *
     * @return void
     * @throws AlreadyExistsException
     */
    public function afterRun($error = null, $onlyStop = null)
    {
        /** @var boolean continue */
        $this->continue = false;
        if ($onlyStop) {
            return;
        }

        if ($error) {
            $this->setJobStatus(JobInterface::JOB_ERROR);
        }

        if ($error === null && $this->currentJob->getStatus() !== JobInterface::JOB_ERROR) {
            $this->eventManager->dispatch(
                'akeneo_connector_import_finish_' . strtolower($this->currentJob->getCode()),
                ['import' => $this]
            );
            $this->currentJob->setLastSuccessDate(date('y-m-d h:i:s'));
            $this->setJobStatus(JobInterface::JOB_SUCCESS);
        }
    }

    /**
     * Description isDone function
     *
     * @return bool
     */
    public function isDone()
    {
        if ($this->continue) {
            return false;
        }

        return true;
    }

    /**
     * Return current message with the timestamp prefix
     *
     * @return string
     */
    public function getMessage()
    {
        return (string)$this->outputHelper->getPrefix() . $this->message;
    }

    /**
     * Return current message with the timestamp prefix
     *
     * @return string
     */
    public function getMessageWithoutPrefix()
    {
        return (string)$this->message;
    }

    /**
     * Display messages from import
     *
     * @param $messages
     *
     * @return void
     */
    public function displayMessages($messages, $logger = null)
    {
        /** @var string[] $importMessages */
        foreach ($messages as $importMessages) {
            if (!empty($importMessages)) {
                /** @var string[] $message */
                foreach ($importMessages as $message) {
                    if (isset($message['message'], $message['status'])) {
                        if ($message['status'] == false) {
                            $this->setMessage($message['message'], $logger);
                            $this->currentJob->setStatus(false);
                        } else {
                            $this->setAdditionalMessage($message['message'], $logger);
                        }
                    }
                }
            }
        }
    }

    /**
     * Set additional message during import
     *
     * @param $message
     *
     * @return $this
     */
    public function setAdditionalMessage($message, $logger = null)
    {
        $this->message = $this->getMessageWithoutPrefix() . $this->getEndOfLine() . $message;
        if ($logger && $this->configHelper->isAdvancedLogActivated()) {
            $this->currentJobClass->getLogger()->addDebug($message);
        }

        return $this;
    }

    /**
     * Get end of line for command line or console
     *
     * @return string
     */
    public function getEndOfLine()
    {
        if ($this->getSetFromAdmin() === false) {
            return PHP_EOL;
        }

        return '</br>';
    }

    /**
     * Set import identifier
     *
     * @param string $identifier
     *
     * @return JobExecutorInterface
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * Get import identifier
     *
     * @return string
     */
    public function getIdentifier()
    {
        if (!$this->identifier) {
            $this->setIdentifier(uniqid());
        }

        return $this->identifier;
    }

    /**
     * Set set from admin
     *
     * @param $value
     *
     * @return $this
     */
    public function setSetFromAdmin($value)
    {
        $this->setFromAdmin = $value;

        return $this;
    }

    /**
     * Get set from admin
     *
     * @return bool
     */
    public function getSetFromAdmin()
    {
        return $this->setFromAdmin;
    }

    /**
     * Display comment in console
     *
     * @param string          $message
     * @param OutputInterface $output
     *
     * @return void
     */
    public function displayComment(string $message)
    {
        if (!empty($message) && $this->output) {
            /** @var string $coloredMessage */
            $coloredMessage = '<comment>' . $message . '</comment>';
            $this->output->writeln($coloredMessage);
        }
    }

    /**
     * Display error in console
     *
     * @param string          $message
     * @param OutputInterface $output
     *
     * @return void
     */
    public function displayError(string $message)
    {
        if (!empty($message) && $this->output) {
            /** @var string $coloredMessage */
            $coloredMessage = '<error>' . $message . '</error>';
            $this->output->writeln($coloredMessage);
        }
    }

    /**
     * Display info in console
     *
     * @param string $message
     *
     * @return void
     */
    public function displayInfo(string $message)
    {
        if (!empty($message) && $this->output) {
            /** @var string $coloredMessage */
            $coloredMessage = '<info>' . $message . '</info>';
            $this->output->writeln($coloredMessage);
        }
    }

    /**
     * Description init function
     *
     * @param string $code
     *
     * @return void
     */
    public function init(string $code)
    {
        /** @var Job $job */
        $job                   = $this->jobRepository->getByCode($code);
        $this->currentJob      = $job;
        $this->currentJobClass = $this->processClassFactory->create($job->getJobClass());
        $this->currentJobClass->setJobExecutor($this);
        $this->currentJobClass->setStatus(true);
    }
}
