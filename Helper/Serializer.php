<?php

namespace Akeneo\Connector\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

/**
 * Class Serializer
 *
 * @category  Class
 * @package   Akeneo\Connector\Helper
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Serializer extends AbstractHelper
{
    /**
     * Unserialize data from config (keep compatibility with Magento < 2.2)
     * This will be replaced by \Magento\Framework\Serialize\Serializer\Json in some time
     *
     * @param string $value
     *
     * @return array
     */
    public function unserialize($value)
    {
        /** @var array $data */
        $data = [];

        if (!$value) {
            return $data;
        }

        try {
            $data = unserialize($value);
        } catch (\Exception $exception) {
            $data = [];
        }

        if (empty($data) && json_decode($value)) {
            $data = json_decode($value, true);
        }

        return $data;
    }
}
