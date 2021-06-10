<?php

namespace Akeneo\Connector\Job;

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
     * This variable contains a bool value
     *
     * @var bool $setFromAdmin
     */
    protected $setFromAdmin;

    /**
     * Import constructor.
     *
     * @param OutputHelper $outputHelper
     * @param ManagerInterface $eventManager
     * @param Authenticator $authenticator
     * @param array $data
     */
    public function __construct(
        OutputHelper $outputHelper,
        ManagerInterface $eventManager,
        Authenticator $authenticator,
        array $data = []
    ) {
        parent::__construct($data);

        $this->authenticator = $authenticator;
        $this->outputHelper = $outputHelper;
        $this->eventManager = $eventManager;
        $this->step         = 0;
        $this->setFromAdmin = false;
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
     * Set additional message during import
     *
     * @param $message
     *
     * @return $this
     */
    public function setAdditionalMessage($message)
    {
        $this->message = $this->getMessageWithoutPrefix() . $this->getEndOfLine() . $message;

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
     * Display messages from import
     *
     * @param $messages
     *
     * @return void
     */
    public function displayMessages($messages) {
        /** @var string[] $importMessages */
        foreach ($messages as $importMessages) {
            if (!empty($importMessages)) {
                /** @var string[] $message */
                foreach ($importMessages as $message) {
                    if (isset($message['message'], $message['status'])) {
                        if ($message['status'] == false) {
                            $this->setMessage($message['message']);
                            $this->setStatus(false);
                        } else {
                            $this->setAdditionalMessage($message['message']);
                        }
                    }
                }
            }
        }
    }
}
