<?php

namespace Akeneo\Connector\Model\ResourceModel\Log;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 *
 * @category  Class
 * @package   Akeneo\Connector\Model\ResourceModel\Log
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Collection extends AbstractCollection
{
    /**
     * This variable contains a string value
     *
     * @var string $_idFieldName
     */
    protected $_idFieldName = 'log_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Akeneo\Connector\Model\Log::class, \Akeneo\Connector\Model\ResourceModel\Log::class);
    }
}
