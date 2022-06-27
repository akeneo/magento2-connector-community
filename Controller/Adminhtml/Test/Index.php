<?php

namespace Akeneo\Connector\Controller\Adminhtml\Test;

use Akeneo\Connector\Helper\Authenticator;
use Akeneo\Connector\Helper\Config as ConfigHelper;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultInterface;
use Exception;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Index extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    public const ADMIN_RESOURCE = 'Magento_Backend::system';
    /**
     * @var Authenticator $authenticator
     */
    protected $authenticator;
    /**
     * This variable contains a ConfigHelper
     *
     * @var ConfigHelper $configHelper
     */
    protected $configHelper;

    /**
     * Index constructor
     *
     * @param Action\Context $context
     * @param Authenticator  $authenticator
     * @param ConfigHelper   $configHelper
     */
    public function __construct(
        Action\Context $context,
        Authenticator $authenticator,
        ConfigHelper $configHelper
    ) {
        parent::__construct($context);

        $this->authenticator = $authenticator;
        $this->configHelper  = $configHelper;
    }

    /**
     * Execute API test
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        try {
            $client = $this->authenticator->getAkeneoApiClient();
            if (!$client) {
                $this->messageManager->addErrorMessage(__('Akeneo API connection error'));
            } else {
                /** @var string|int $paginationSize */
                $paginationSize = $this->configHelper->getPaginationSize();
                $client->getChannelApi()->all($paginationSize);
                $this->messageManager->addSuccessMessage(__('The connection is working fine'));
            }
        } catch (Exception $ext) {
            $this->messageManager->addErrorMessage($ext->getMessage());
        }

        return $resultRedirect->setUrl($this->_redirect->getRefererUrl());
    }
}
