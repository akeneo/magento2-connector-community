<?php

namespace Akeneo\Connector\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

/**
 * Class AttributeHandler
 *
 * @package   Akeneo\Connector\Logger\Handler
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class AttributeHandler extends Base
{
    /**
     * Logging level
     *
     * @var int
     */
    protected $loggerType = Logger::DEBUG;
    /**
     * File name
     *
     * @var string
     */
    protected $fileName = '/var/log/akeneo_connector/attribute-import.log';

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
