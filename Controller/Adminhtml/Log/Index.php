<?php

namespace Akeneo\Connector\Controller\Adminhtml\Log;

use Magento\Backend\App\Action;

/**
 * Class Index
 *
 * @category  Class
 * @package   Akeneo\Connector\Controller\Adminhtml\Log
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Index extends Action
{
    /**
     * Action
     *
     * @return void
     */
    public function execute()
    {
        $this->_view->loadLayout();

        $this->_setActiveMenu('Magento_Backend::system');
        $this->_addBreadcrumb(__('Akeneo Connector'), __('Log'));

        $this->_view->renderLayout();
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Akeneo_Connector::akeneo_connector_log');
    }
}
