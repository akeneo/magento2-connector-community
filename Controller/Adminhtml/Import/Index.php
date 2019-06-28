<?php

namespace Akeneo\Connector\Controller\Adminhtml\Import;

use Magento\Backend\App\Action;

/**
 * Class Index
 *
 * @category  Class
 * @package   Akeneo\Connector\Controller\Adminhtml\Import
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Index extends Action
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $this->_view->loadLayout();

        $this->_setActiveMenu('Magento_Backend::system');
        $this->_addBreadcrumb(__('Akeneo Connector'), __('Import'));

        $this->_view->renderLayout();
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Akeneo_Connector::import');
    }
}
