<?php

namespace Akeneo\Connector\Block\Adminhtml\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

/**
 * Class Attribute
 *
 * @category  Class
 * @package   Akeneo\Connector\Block\Adminhtml\System\Config\Form\Field
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Attribute extends AbstractFieldArray
{
    /**
     * Initialise form fields
     *
     * @return void
     */
    protected function _construct()
    {
        $this->addColumn('akeneo_attribute', ['label' => __('Akeneo')]);
        $this->addColumn('magento_attribute', ['label' => __('Magento')]);
        $this->_addAfter       = false;
        $this->_addButtonLabel = __('Add');

        parent::_construct();
    }
}
