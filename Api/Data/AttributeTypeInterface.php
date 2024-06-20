<?php

declare(strict_types = 1);

namespace Akeneo\Connector\Api\Data;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
interface AttributeTypeInterface
{
    public const PIM_CATALOG_IDENTIFIER = 'pim_catalog_identifier';
    public const PIM_CATALOG_TEXT = 'pim_catalog_text';
    public const PIM_CATALOG_TEXTAREA = 'pim_catalog_textarea';
    public const PIM_CATALOG_SIMPLESELECT = 'pim_catalog_simpleselect';
    public const PIM_CATALOG_MULTISELECT = 'pim_catalog_multiselect';
    public const PIM_CATALOG_BOOLEAN = 'pim_catalog_boolean';
    public const PIM_CATALOG_DATE = 'pim_catalog_date';
    public const PIM_CATALOG_NUMBER = 'pim_catalog_number';
    public const PIM_CATALOG_METRIC = 'pim_catalog_metric';
    public const PIM_CATALOG_PRICE_COLLECTION = 'pim_catalog_price_collection';
    public const PIM_CATALOG_IMAGE = 'pim_catalog_image';
    public const PIM_CATALOG_FILE = 'pim_catalog_file';
    public const PIM_CATALOG_ASSET_COLLECTION = 'pim_catalog_asset_collection';
    public const AKENEO_REFERENCE_ENTITY = 'akeneo_reference_entity';
    public const AKENEO_REFERENCE_ENTITY_COLLECTION = 'akeneo_reference_entity_collection';
    public const PIM_REFERENCE_DATA_SIMPLESELECT = 'pim_reference_data_simpleselect';
    public const PIM_REFERENCE_DATA_MULTISELECT = 'pim_reference_data_multiselect';
    public const PIM_CATALOG_TABLE = 'pim_catalog_table';
}
