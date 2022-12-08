<?php

declare(strict_types=1);

namespace Akeneo\Connector\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class FamilyHandler extends Base
{
    /**
     * Logging level
     *
     * @var int
     */
    protected $loggerType = Logger::DEBUG;
    /**
     * Define file name path
     *
     * @var string
     */
    protected $fileName = '/var/log/akeneo_connector/family-import.log';

    /**
     * Get log filemane
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->fileName;
    }
}
