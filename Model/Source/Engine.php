<?php

declare(strict_types=1);

namespace Akeneo\Connector\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * @author  Agence Dn'D <contact@dnd.fr>
 * @license https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link    https://www.dnd.fr/
 */
class Engine implements OptionSourceInterface
{
    public const STORAGE_ENGINE_MYISAM = 'myisam';

    public const STORAGE_ENGINE_INNODB = 'innodb';

    public function toOptionArray(): array
    {
        return [
            self::STORAGE_ENGINE_MYISAM => __('MyISAM'),
            self::STORAGE_ENGINE_INNODB => __('InnoDB'),
        ];
    }
}
