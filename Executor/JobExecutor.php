<?php

declare(strict_types=1);

namespace Akeneo\Connector\Executor;

use Akeneo\Connector\Api\Data\JobInterface;
use Akeneo\Connector\Api\JobExecutorInterface;
use Akeneo\Connector\Helper\Authenticator;
use Akeneo\Connector\Helper\Config as ConfigHelper;
use Akeneo\Connector\Helper\Output as OutputHelper;
use Akeneo\Connector\Job\Import;
use Akeneo\Connector\Model\Job;
use Akeneo\Connector\Model\JobRepository;
use Akeneo\Connector\Model\Processor\ProcessClassFactory;
use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;
use Magento\Framework\DataObject;
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
     * @var Job $currentJob
     */
    protected $currentJobClass;
    /**
     * This variable contains a AkeneoPimEnterpriseClientInterface
     *
     * @var AkeneoPimClientInterface|AkeneoPimEnterpriseClientInterface $akeneoClient
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
     * JobExecutor constructor
     *
     * @param \Akeneo\Connector\Model\JobRepository                 $jobRepository
     * @param \Akeneo\Connector\Model\Processor\ProcessClassFactory $processClassFactory
     * @param \Akeneo\Connector\Helper\Config                       $configHelper
     * @param \Akeneo\Connector\Helper\Output                       $outputHelper
     * @param \Magento\Framework\Event\ManagerInterface             $eventManager
     * @param \Akeneo\Connector\Helper\Authenticator                $authenticator
     */
    public function __construct(
        JobRepository $jobRepository,
        ProcessClassFactory $processClassFactory,
        ConfigHelper $configHelper,
        OutputHelper $outputHelper,
        ManagerInterface $eventManager,
        Authenticator $authenticator
    ) {
        $this->jobRepository       = $jobRepository;
        $this->processClassFactory = $processClassFactory;
        $this->configHelper        = $configHelper;
        $this->outputHelper        = $outputHelper;
        $this->eventManager        = $eventManager;
        $this->authenticator       = $authenticator;
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
     * Run import
     *
     * @param string $code
     *
     * @return bool
     */
    public function execute(string $code)
    {
        /** @var Job $job */
        $job = $this->jobRepository->getByCode($code);
        if (!$job) {
            /** @var Phrase $message */
            $message = __('Job code not found');

            //$this->displayError($message, $output);

            return false;
        }
        $this->currentJob      = $job;
        $this->currentJobClass = $this->processClassFactory->create($job->getJobClass());

        if (!$this->configHelper->checkAkeneoApiCredentials()) {
            /** @var Phrase $message */
            $message = __('API credentials are missing. Please configure the connector and retry.');

            //$this->displayError($message, $output);

            return false;
        }

        // If product import, run the import once per family
        /** @var array $productFamiliesToImport */
        $productFamiliesToImport = [];
        if ($code == self::IMPORT_CODE_PRODUCT) {
            $productFamiliesToImport = $this->currentJobClass->getFamiliesToImport();

            if (!count($productFamiliesToImport)) {
                $message = __('No family to import');

                ////$this->displayError($message, $output);

                return false;
            }

            foreach ($productFamiliesToImport as $family) {
                $this->runImport($family);
                //$import->setIdentifier(null);
            }

            return true;
        }

        // Run the import normaly
        $this->runImport();

        return true;
    }

    /**
     * Run the import
     *
     * @param null $family
     *
     * @return bool
     */
    protected function runImport($family = null)
    {
        try {
            $this->initSteps();
            $this->setStep(0);
            if ($family) {
                $this->setFamily($family);
            }

            while ($this->canExecute()) {
                /** @var string $comment */
                $comment = $this->getComment();
                //$this->displayInfo($comment);
                $this->executeStep();

                /** @var string $message */
                $message = $this->getMessage();
                if (!$this->getStatus()) {
                    //$this->displayError($message, $output);
                } else {
                    //$this->displayComment($message, $output);
                }

                if ($this->isDone()) {
                    $this->setJobStatus(JobInterface::JOB_PROCESSING);
                    break;
                }
            }
        } catch (\Exception $exception) {
            /** @var string $message */
            $message = $exception->getMessage();
            //$this->displayError($message, $output);
        }

        return true;
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
     * Get import status
     *
     * @return bool
     */
    public function getStatus()
    {
        return $this->status;
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
     * Check if import may be processed (Not already running, ...)
     *
     * @return bool
     */
    public function canExecute()
    {
        if ($this->step < 0 || $this->step > $this->countSteps()) {
            return false;
        }

        return true;
    }

    /**
     * Function called to run import
     * This function will get the right method to call
     *
     * @return array
     */
    public function executeStep()
    {
        if (!$this->canExecute() || !isset($this->steps[$this->step])) {
            return $this->outputHelper->getImportAlreadyRunningResponse();
        };

        /** @var string $method */
        $method = $this->getMethod();
        if (!method_exists($this->currentJobClass, $method)) {
            $this->stop(true);

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
            ['import' => $this->currentJobClass, 'executor' => $this]
        );
        $this->eventManager->dispatch(
            'akeneo_connector_import_step_start_' . strtolower($this->currentJob->getCode()),
            ['import' => $this->currentJobClass]
        );

        $this->initStatus();

        try {
            $this->currentJobClass->{$method}();
        } catch (\Exception $exception) {
            $this->stop(true);
            $this->setMessage($exception->getMessage());
        }

        $this->eventManager->dispatch('akeneo_connector_import_step_finish', ['import' => $this->currentJobClass]);
        $this->eventManager->dispatch(
            'akeneo_connector_import_step_finish_' . strtolower($this->currentJob->getCode()),
            ['import' => $this]
        );

        return $this->getResponse();
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
     * Format data to response structure
     *
     * @return array
     */
    protected function getResponse()
    {
        /** @var array $response */
        $response = [
            'continue'   => $this->continue,
            'identifier' => $this->currentJob->getCode(),
            'status'     => $this->getStatus(),
        ];

        if ($this->getComment()) {
            $response['comment'] = $this->getComment();
        }

        if ($this->message) {
            $response['message'] = $this->getMessage();
        }

        if (!$this->isDone()) {
            $response['next'] = $this->nextStep()->getComment();
        }

        return $response;
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
        $this->setStatus(true);
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
     * Stop the import (no step will be processed after)
     *
     * @param bool $error
     *
     * @return void
     */
    public function stop($error = false)
    {
        $this->continue = false;
        if ($error == true) {
            $this->setStatus(false);
        }
    }

    /**
     * Get Akeneo Client instance
     *
     * @return AkeneoPimEnterpriseClientInterface|false
     */
    public function getAkeneoClient()
    {
        try {
            /** @var AkeneoPimEnterpriseClientInterface|false $akeneoClient */
            $akeneoClient = $this->authenticator->getAkeneoApiClient();
        } catch (\Exception $e) {
            $akeneoClient = false;
        }

        return $akeneoClient;
    }

    /**
     * Display info in console
     *
     * @param string          $message
     * @param OutputInterface $output
     *
     * @return void
     */
    public function displayInfo(string $message, OutputInterface $output)
    {
        if (!empty($message)) {
            /** @var string $coloredMessage */
            $coloredMessage = '<info>' . $message . '</info>';
            $output->writeln($coloredMessage);
        }
    }

    /**
     * Display comment in console
     *
     * @param string          $message
     * @param OutputInterface $output
     *
     * @return void
     */
    public function displayComment(string $message, OutputInterface $output)
    {
        if (!empty($message)) {
            /** @var string $coloredMessage */
            $coloredMessage = '<comment>' . $message . '</comment>';
            $output->writeln($coloredMessage);
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
    public function displayError(string $message, OutputInterface $output)
    {
        if (!empty($message)) {
            /** @var string $coloredMessage */
            $coloredMessage = '<error>' . $message . '</error>';
            $output->writeln($coloredMessage);
        }
    }

    /**
     * Set import message
     *
     * @param string|Phrase $message
     *
     * @return JobExecutor
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Set import status
     *
     * @param $status
     *
     * @return JobExecutor
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Description setJobStatus function
     *
     * @param int $status
     *
     * @return void
     * @throws AlreadyExistsException
     */
    public function setJobStatus(int $status)
    {
        $this->currentJob->setStatus($status);
        $this->jobRepository->save($this->currentJob);
    }

    /**
     * {@inheritdoc}
     */
    public function beforeImport()
    {
        if ($this->akeneoClient === false) {
            $this->setMessage(
                __(
                    'Could not start the import %s, check that your API credentials are correctly configured',
                    $this->currentJob->getCode()
                )
            );
            $this->stop(1);

            return;
        }

        /** @var string $identifier */
        $identifier = $this->getIdentifier();

        $this->setMessage(__('Import ID : %1', $identifier));
    }

    /**
     * Function called after any step
     *
     * @return void
     */
    public function afterImport()
    {
        $this->setMessage(__('Import ID : %1', $this->identifier))->stop();
    }

    /**
     * Description beforeRun function
     *
     * @return void
     * @throws AlreadyExistsException
     */
    public function beforeRun()
    {
        $this->eventManager->dispatch(
            'akeneo_connector_import_start',
            ['import' => $this->currentJobClass, 'executor' => $this]
        );
        $this->setJobStatus(JobInterface::JOB_PROCESSING);
    }

    /**
     * Description afterRun function
     *
     * @return void
     * @throws AlreadyExistsException
     */
    public function afterRun()
    {
        $this->eventManager->dispatch(
            'akeneo_connector_import_finish_' . strtolower($this->currentJob->getCode()),
            ['import' => $this]
        );
        $this->setJobStatus(JobInterface::JOB_SUCCESS);
    }
}
