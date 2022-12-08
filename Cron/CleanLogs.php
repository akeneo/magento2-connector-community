<?php

declare(strict_types=1);

namespace Akeneo\Connector\Cron;

use Akeneo\Connector\Api\Data\LogInterface;
use Akeneo\Connector\Helper\Config as ConfigHelper;
use Akeneo\Connector\Helper\Import\Entities;
use DateTime;
use Exception;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Statement\Pdo\Mysql;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class CleanLogs
{
    /**
     * Description $configHelper field
     *
     * @var ConfigHelper $configHelper
     */
    protected $configHelper;
    /**
     * This variable contains a TimezoneInterface
     *
     * @var TimezoneInterface $timezone
     */
    protected $timezone;
    /**
     * This variable contains a Entities
     *
     * @var Entities $entitiesHelper
     */
    protected $entitiesHelper;

    /**
     * CleanLogs constructor
     *
     * @param ConfigHelper      $configHelper
     * @param TimezoneInterface $timezone
     * @param Entities          $entitiesHelper
     */
    public function __construct(
        ConfigHelper $configHelper,
        TimezoneInterface $timezone,
        Entities $entitiesHelper
    ) {
        $this->configHelper   = $configHelper;
        $this->timezone       = $timezone;
        $this->entitiesHelper = $entitiesHelper;
    }

    /**
     * Description execute function
     *
     * @return CleanLogs
     * @throws Exception
     */
    public function execute(): CleanLogs
    {
        /** @var string $ifConfigEnable */
        $ifConfigEnable = $this->configHelper->getEnableCleanLogs();
        if (!$ifConfigEnable) {
            return $this;
        }
        /** @var string $valueConfig */
        $valueConfig = $this->configHelper->getCleanLogs();
        if (!$valueConfig) {
            return $this;
        }
        /** @var int $filter */
        $filter = ((int)$valueConfig) * 86400;
        if (!is_numeric($filter)) {
            return $this;
        }
        /** @var int $currentDateTime */
        $currentDateTime = $this->timezone->date()->getTimestamp();

        /** @var int $timestamp */
        $timestamp = $currentDateTime - $filter;

        /** @var string $date */
        $date = (new DateTime())->setTimestamp($timestamp)->format('Y-m-d H:i:s');

        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();

        /** @var string $logTable */
        $logTable = $this->entitiesHelper->getTable(LogInterface::AKENEO_CONNECTOR_IMPORT_LOG);

        /** @var Select $select */
        $select = $connection->select()->from($logTable, LogInterface::IDENTIFIER)->where('created_at <= ?', $date);

        /** @var Mysql $query */
        $query = $connection->query($select);

        /** @var mixed[] $logRecords */
        $logRecords = $query->fetchAll();

        /** @var string[] $value */
        foreach ($logRecords as $value) {
            $connection->delete(
                LogInterface::AKENEO_CONNECTOR_IMPORT_LOG,
                ['identifier = ?' => $value[LogInterface::IDENTIFIER]]
            );
            $connection->delete(
                LogInterface::AKENEO_CONNECTOR_IMPORT_LOG_STEP,
                ['identifier = ?' => $value[LogInterface::IDENTIFIER]]
            );
        }

        return $this;
    }
}
