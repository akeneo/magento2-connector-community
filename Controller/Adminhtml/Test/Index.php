<?php

namespace Akeneo\Connector\Controller\Adminhtml\Test;

use Akeneo\Connector\Helper\Authenticator;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultInterface;
use Exception;

/**
 * Class Index
 *
 * @category  Class
 * @package   Akeneo\Connector\Controller\Adminhtml\Test
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Index extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Magento_Backend::system';

    /**
     * @var Authenticator $authenticator
     */
    protected $authenticator;

    /**
     * Index constructor
     *
     * @param Action\Context $context
     * @param Authenticator $authenticator
     */
    public function __construct(
        Action\Context $context,
        Authenticator $authenticator
    ) {
        parent::__construct($context);

        $this->authenticator = $authenticator;
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
                $client->getChannelApi()->all();
                $this->messageManager->addSuccessMessage(__('The connection is working fine'));
            }
        } catch (Exception $ext) {
            $this->messageManager->addErrorMessage($ext->getMessage());
        }

        return $resultRedirect->setUrl($this->_redirect->getRefererUrl());
    }
}
