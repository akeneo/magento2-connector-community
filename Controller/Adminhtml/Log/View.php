<?php

declare(strict_types=1);

namespace Akeneo\Connector\Controller\Adminhtml\Log;

use Magento\Backend\App\Action;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class View extends Action
{
    /**
     * Action
     *
     * @return void
     */
    public function execute()
    {
        $this->_view->loadLayout();

        /* @var $block \Akeneo\Connector\Block\Adminhtml\Log\View */
        $block = $this->_view->getLayout()->getBlock('adminhtml.akeneo_connector.log.view');
        $block->setLogId(
            $this->getRequest()->getParam('log_id')
        );

        $this->_setActiveMenu('Magento_Backend::system');
        $this->_addBreadcrumb(__('Akeneo Connector'), __('Log'));

        $this->_view->renderLayout();
    }

    /**
     * Description isAllowed function
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Akeneo_Connector::akeneo_connector_log');
    }
}
