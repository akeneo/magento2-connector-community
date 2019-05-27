<?php

namespace Akeneo\Connector\Helper\Import;

/**
 * Class FamilyVariant
 *
 * @category  Class
 * @package   Akeneo\Connector\Helper\Import
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class FamilyVariant extends Entities
{
    /**
     * Get columns from the api result
     *
     * @param array $result
     *
     * @return array
     */
    protected function getColumnsFromResult(array $result)
    {
        /** @var array $columns */
        $columns = [];
        /**
         * @var string $key
         * @var mixed $value
         */
        foreach ($result as $key => $value) {
            if (in_array($key, static::EXCLUDED_COLUMNS)) {
                continue;
            }
            $columns[$key] = $value;

            if (!is_array($value)) {
                continue;
            }
            if (empty($value)) {
                $columns[$key] = null;

                continue;
            }
            unset($columns[$key]);
            /**
             * @var string|int $local
             * @var string|array $data
             */
            foreach ($value as $local => $data) {
                if ($key == 'variant_attribute_sets') {
                    $columns['variant-axes_'.$data['level']]       = strtolower(join(',', $data['axes']));
                    $columns['variant-attributes_'.$data['level']] = strtolower(join(',', $data['attributes']));

                    continue;
                }
                if (!is_numeric($local)) {
                    if (is_array($data)) {
                        $data = join(',', $data);
                    }
                    $columns[$key.'-'.$local] = $data;
                } else {
                    $columns[$key] = join(',', $value);
                }
            }
        }

        return $columns;
    }
}
