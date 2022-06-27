<?php

namespace Akeneo\Connector\Controller\Adminhtml\Job;

use Magento\Backend\App\Action;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Index extends Action
{
    /**
     * Description execute function
     *
     * @return void
     */
    public function execute()
    {
        $this->_view->loadLayout();

        $this->_setActiveMenu('Magento_Backend::system');
        $this->_addBreadcrumb(__('Akeneo Connector'), __('Job'));

        $this->_view->renderLayout();
    }

    /**
     * Description _isAllowed function
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Akeneo_Connector::akeneo_connector_job');
    }
}
