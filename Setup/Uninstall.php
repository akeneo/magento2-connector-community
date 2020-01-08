<?php

namespace Akeneo\Connector\Setup;

use Magento\Framework\Setup\UninstallInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

/**
 * Class
 *
 * @package   Class
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Uninstall implements UninstallInterface
{
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        /** @var SchemaSetupInterface $installer */
        $installer = $setup;
        $installer->startSetup();

        $installer->getConnection()->dropTrigger('akeneo_connector_after_delete_category');
        $installer->getConnection()->dropTrigger('akeneo_connector_after_delete_family');
        $installer->getConnection()->dropTrigger('akeneo_connector_after_delete_product');
        $installer->getConnection()->dropTrigger('akeneo_connector_after_delete_option');
        $installer->getConnection()->dropTrigger('akeneo_connector_after_delete_attribute');

        $installer->endSetup();
    }
}