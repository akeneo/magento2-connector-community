<?php

namespace Akeneo\Connector\Job;

use Akeneo\Connector\Helper\Config;
use Akeneo\Connector\Helper\Import\Entities;
use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Phrase;
use Akeneo\Connector\Api\Data\ImportInterface;
use Akeneo\Connector\Helper\Authenticator;
use Akeneo\Connector\Helper\Output as OutputHelper;
use Zend_Db_Statement_Exception;

/**
 * Class Import
 *
 * @category  Class
 * @package   Akeneo\Connector\Job
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
abstract class Import extends DataObject implements ImportInterface
{
    /**
     * This variable contains a string
     *
     * @var string $code
     */
    protected $code;
    /**
     * This variable contains a string value
     *
     * @var string $name
     */
    protected $name;
    /**
     * This variable contains a string value
     *
     * @var string $identifier
     */
    protected $identifier;
    /**
     * This variable contains a boolean
     *
     * @var bool $status
     */
    protected $status;
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
     * This variable contains an OutputHelper
     *
     * @var OutputHelper $outputHelper
     */
    protected $outputHelper;
    /**
     * This variable contains a Config
     *
     * @var Config $configHelper
     */
    protected $configHelper;
    /**
     * This variable contains an Authenticator
     *
     * @var mixed $authenticator
     */
    protected $authenticator;
    /**
     * This variable contains a mixed value
     *
     * @var ManagerInterface $eventManager
     */
    protected $eventManager;
    /**
     * This variable contains a AkeneoPimClientInterface
     *
     * @var AkeneoPimClientInterface $akeneoClient
     */
    protected $akeneoClient;
    /**
     * This variable contains a string or Phrase value
     *
     * @var string|Phrase $comment
     */
    protected $comment;
    /**
     * This variable contains a string or Phrase value
     *
     * @var string|Phrase $message
     */
    protected $message;
    /**
     * This variable contains a bool value
     *
     * @var bool $continue
     */
    protected $continue;
    /**
     * This variable contains a bool value
     *
     * @var bool $setFromAdmin
     */
    protected $setFromAdmin;
    /**
     * This variable contains an EntitiesHelper
     *
     * @var Entities $entitiesHelper
     */
    protected $entitiesHelper;

    /**
     * Import constructor.
     *
     * @param OutputHelper     $outputHelper
     * @param ManagerInterface $eventManager
     * @param Authenticator    $authenticator
     * @param Entities         $entitiesHelper
     * @param Config           $configHelper
     * @param array            $data
     */
    public function __construct(
        OutputHelper $outputHelper,
        ManagerInterface $eventManager,
        Authenticator $authenticator,
        Entities $entitiesHelper,
        Config $configHelper,
        array $data = []
    ) {
        parent::__construct($data);

        $this->authenticator  = $authenticator;
        $this->outputHelper   = $outputHelper;
        $this->eventManager   = $eventManager;
        $this->entitiesHelper = $entitiesHelper;
        $this->configHelper   = $configHelper;
        $this->step           = 0;
        $this->setFromAdmin = false;
        $this->initStatus();
        $this->initSteps();
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
        if ($this->getData('steps')) {
            $steps = $this->getData('steps');
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
     * Get import code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Get import name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set import identifier
     *
     * @param string $identifier
     *
     * @return Import
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
     * Set current step index
     *
     * @param int $step
     *
     * @return Import
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
     * Set import comment
     *
     * @param string|Phrase $comment
     *
     * @return Import
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Set import message
     *
     * @param string|Phrase $message
     * @param Logger|null   $logger
     *
     * @return Import
     */
    public function setMessage($message, $logger = null)
    {
        $this->message = $message;
        if ($logger && $this->configHelper->isAdvancedLogActivated()) {
            $this->logger->addDebug($message);
        }

        return $this;
    }

    /**
     * Set additional message during import
     *
     * @param string|Phrase $message
     * @param Logger|null   $logger
     *
     * @return $this
     */
    public function setAdditionalMessage($message, $logger = null)
    {
        $this->message = $this->getMessageWithoutPrefix() . $this->getEndOfLine() . $message;
        if ($logger && $this->configHelper->isAdvancedLogActivated()) {
            $this->logger->addDebug($message);
        }

        return $this;
    }

    /**
     * Set import status
     *
     * @param $status
     *
     * @return Import
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
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
     * Set continue
     *
     * @param bool $continue
     *
     * @return Import
     */
    public function setContinue($continue)
    {
        $this->continue = $continue;

        return $this;
    }

    /**
     * Get the prefixed comment
     *
     * @return string
     */
    public function getComment()
    {
        return isset($this->steps[$this->getStep()]['comment']) ?
            $this->outputHelper->getPrefix() . $this->steps[$this->getStep()]['comment'] :
            $this->outputHelper->getPrefix() . get_class($this) . '::' . $this->getMethod();
    }

    /**
     * Return current message with the timestamp prefix
     *
     * @return string
     */
    public function getMessage()
    {
        return (string)$this->outputHelper->getPrefix().$this->message;
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
     * Get method to execute
     *
     * @return string
     */
    public function getMethod()
    {
        return isset($this->steps[$this->getStep()]['method']) ?
            $this->steps[$this->getStep()]['method'] : null;
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
     * Function called to run import
     * This function will get the right method to call
     *
     * @return array
     */
    public function execute()
    {
        if (!$this->canExecute() || !isset($this->steps[$this->step])) {
            return $this->outputHelper->getImportAlreadyRunningResponse();
        };

        /** @var string $method */
        $method = $this->getMethod();
        if (!method_exists($this, $method)) {
            $this->stop(true);

            return $this->outputHelper->getNoImportFoundResponse();
        }

        if (!$this->akeneoClient) {
            $this->akeneoClient = $this->getAkeneoClient();
        }

        if (!$this->akeneoClient) {
            return $this->outputHelper->getApiConnectionError();
        }

        $this->eventManager->dispatch('akeneo_connector_import_step_start', ['import' => $this]);
        $this->eventManager->dispatch(
            'akeneo_connector_import_step_start_'.strtolower($this->getCode()),
            ['import' => $this]
        );
        $this->initStatus();

        try {
            $this->{$method}();
        } catch (\Exception $exception) {
            $this->stop(true);
            $this->setMessage($exception->getMessage());
        }

        $this->eventManager->dispatch('akeneo_connector_import_step_finish', ['import' => $this]);
        $this->eventManager->dispatch(
            'akeneo_connector_import_step_finish_'.strtolower($this->getCode()),
            ['import' => $this]
        );

        return $this->getResponse();
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
     * Format data to response structure
     *
     * @return array
     */
    protected function getResponse()
    {
        /** @var array $response */
        $response = [
            'continue'   => $this->continue,
            'identifier' => $this->getIdentifier(),
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
     * {@inheritdoc}
     */
    public function beforeImport()
    {
        if ($this->akeneoClient === false) {
            $this->setMessage(__('Could not start the import %s, check that your API credentials are correctly configured', $this->getCode()));
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
     * Description hasError function
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
     * Increment the step
     *
     * @return Import
     */
    public function nextStep()
    {
        $this->step += 1;

        return $this;
    }

    /**
     * Check if all locales labels exists
     *
     * @param string[] $entity
     * @param string[] $lang
     * @param string $response
     *
     * @return string
     */
    public function checkLabelPerLocales(array $entity, array $lang, string $response)
    {
        /** @var string[] $labels */
        $labels = $entity['labels'];
        foreach ($lang as $locale => $stores) {
            if (empty($labels[$locale])) {
                $response .= __("Label for '%1' in %2 is missing. ", $entity['code'], $locale);
            }
        }
        return $response;
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
     * Display messages from import
     *
     * @param $messages
     *
     * @return void
     */
    public function displayMessages($messages, $logger) {
        /** @var string[] $importMessages */
        foreach ($messages as $importMessages) {
            if (!empty($importMessages)) {
                /** @var string[] $message */
                foreach ($importMessages as $message) {
                    if (isset($message['message'], $message['status'])) {
                        if ($message['status'] == false) {
                            $this->setMessage($message['message'], $logger);
                            $this->setStatus(false);
                        } else {
                            $this->setAdditionalMessage($message['message'], $logger);
                        }
                    }
                }
            }
        }
    }

    public function logImportedEntities($logger = null, $newEntities = false, $identifierColumn = 'code')
    {
        if ($logger) {
            /** @var AdapterInterface $connection */
            $connection = $this->entitiesHelper->getConnection();
            /** @var string $tmpTable */
            $tmpTable = $this->entitiesHelper->getTableName($this->getCode());
            /** @var \Magento\Framework\DB\Select $selectExistingEntities */
            $selectImportedEntities = $connection->select()->from($tmpTable, $identifierColumn);
            if ($newEntities) {
                $selectImportedEntities = $selectImportedEntities->where('_is_new = ?', '1');
            }
            /** @var string[] $existingEntities */
            $existingEntities = array_column(
                $connection->query($selectImportedEntities)->fetchAll(),
                $identifierColumn
            );
            if ($newEntities) {
                $logger->addDebug(__('Imported new entities : %1', implode(',', $existingEntities)));

                return;
            }
            $logger->addDebug(__('Imported entities : %1', implode(',', $existingEntities)));
        }
    }
}
