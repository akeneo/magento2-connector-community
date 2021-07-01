<?php

namespace Akeneo\Connector\Job;

use Akeneo\Connector\Executor\JobExecutor;
use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Phrase;
use Akeneo\Connector\Api\Data\ImportInterface;
use Akeneo\Connector\Helper\Authenticator;
use Akeneo\Connector\Helper\Output as OutputHelper;

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
     * This variable contains a string or Phrase value
     *
     * @var string|Phrase $comment
     */
    protected $comment;
    /**
     * This variable contains a AkeneoPimEnterpriseClientInterface
     *
     * @var AkeneoPimClientInterface|AkeneoPimEnterpriseClientInterface $akeneoClient
     */
    protected $akeneoClient;
    /**
     * Current jobExecutor
     *
     * @var JobExecutor $jobExecutor
     */
    protected $jobExecutor;

    /**
     * Import constructor.
     *
     * @param OutputHelper     $outputHelper
     * @param ManagerInterface $eventManager
     * @param Authenticator    $authenticator
     * @param array            $data
     */
    public function __construct(
        OutputHelper $outputHelper,
        ManagerInterface $eventManager,
        Authenticator $authenticator,
        array $data = []
    ) {
        parent::__construct($data);

        $this->authenticator = $authenticator;
        $this->outputHelper  = $outputHelper;
        $this->eventManager  = $eventManager;
        $this->setFromAdmin  = false;
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
     * {@inheritdoc}
     */
    public function beforeImport()
    {
        if ($this->akeneoClient === false) {
            $this->setMessage(
                __(
                    'Could not start the import %s, check that your API credentials are correctly configured',
                    $this->getCode()
                )
            );
            $this->stop(1);

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
        $this->jobExecutor->setMessage(__('Import ID : %1', $this->jobExecutor->getIdentifier()))->afterRun()
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

    public function setJobExecutor(JobExecutor $jobExecutor)
    {
        $this->jobExecutor = $jobExecutor;
        $this->akeneoClient = $jobExecutor->getAkeneoClient();
    }
}
