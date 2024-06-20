<?php

declare(strict_types=1);

namespace Akeneo\Connector\Job;

use Akeneo\Connector\Api\Data\JobInterface;
use Akeneo\Connector\Executor\JobExecutor;
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
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
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
     * This variable contains a string or Phrase value
     *
     * @var string|Phrase $comment
     */
    protected $comment;
    /**
     * This variable contains a AkeneoPimClientInterface
     *
     * @var AkeneoPimClientInterface $akeneoClient
     */
    protected $akeneoClient;
    /**
     * Current jobExecutor
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
     * This variable contains an EntitiesHelper
     *
     * @var Entities $entitiesHelper
     */
    protected $entitiesHelper;
    /**
     * This variable contains a boolean
     *
     * @var bool $status
     */
    protected $status;

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
     * @inheritdoc
     */
    public function beforeImport()
    {
        if ($this->akeneoClient === false) {
            $this->jobExecutor->setMessage(
                __(
                    'Could not start the import %s, check that your API credentials are correctly configured',
                    $this->jobExecutor->getCurrentJob()->getCode()
                )
            );
            $this->jobExecutor->afterRun(true);

            return;
        }

        /** @var string $identifier */
        $identifier = $this->jobExecutor->getIdentifier();

        $this->jobExecutor->setMessage(__('Import ID : %1', $identifier));
    }

    /**
     * Function called after any step
     *
     * @return void
     */
    public function afterImport()
    {
        $this->jobExecutor->setMessage(__('Import ID : %1', $this->jobExecutor->getIdentifier()))->afterRun(null, true);
    }

    /**
     * Check if all locales labels exists
     *
     * @param string[] $entity
     * @param string[] $lang
     * @param string   $response
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
     * Description setJobExecutor function
     *
     * @param JobExecutor $jobExecutor
     *
     * @return void
     */
    public function setJobExecutor(JobExecutor $jobExecutor)
    {
        $this->jobExecutor  = $jobExecutor;
        $this->akeneoClient = $jobExecutor->getAkeneoClient();
    }

    /**
     * Set import status
     *
     * @param bool $status
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
     * Description logImportedEntities function
     *
     * @param null   $logger
     * @param false  $newEntities
     * @param string $identifierColumn
     *
     * @return void
     * @throws Zend_Db_Statement_Exception
     */
    public function logImportedEntities($logger = null, $newEntities = false, $identifierColumn = 'code')
    {
        if ($logger) {
            /** @var AdapterInterface $connection */
            $connection = $this->entitiesHelper->getConnection();
            /** @var string $tmpTable */
            $tmpTable = $this->entitiesHelper->getTableName($this->jobExecutor->getCurrentJob()->getCode());
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
                $logger->debug(__('Imported new entities : %1', implode(',', $existingEntities)));

                return;
            }
            $logger->debug(__('Imported entities : %1', implode(',', $existingEntities)));
        }
    }
}
