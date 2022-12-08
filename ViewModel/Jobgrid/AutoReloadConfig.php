<?php

declare(strict_types=1);

namespace Akeneo\Connector\ViewModel\Jobgrid;

use Akeneo\Connector\Api\Data\JobInterface;
use Akeneo\Connector\Model\ResourceModel\Job\CollectionFactory as JobCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class AutoReloadConfig implements ArgumentInterface
{
    /**
     * Is auto reload config path
     *
     * @var string IS_AUTO_RELOAD_CONFIG_PATH
     */
    public const IS_AUTO_RELOAD_CONFIG_PATH = 'akeneo_connector/advanced/enable_job_grid_auto_reload';
    /**
     * Scheduling and processing job status ids
     *
     * @var int[] SCHEDULING_AND_PROCESSING_STATUS_IDS
     */
    public const SCHEDULING_AND_PROCESSING_STATUS_IDS = [
        JobInterface::JOB_SCHEDULED,
        JobInterface::JOB_PROCESSING,
    ];

    protected Json $json;
    protected ScopeConfigInterface $scopeConfig;
    protected JobCollectionFactory $jobCollectionFactory;

    /**
     * @param JobCollectionFactory $jobCollectionFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param Json $json
     */
    public function __construct(
        JobCollectionFactory $jobCollectionFactory,
        ScopeConfigInterface $scopeConfig,
        Json $json
    ) {
        $this->jobCollectionFactory = $jobCollectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->json = $json;
    }

    /**
     * Should reload be enabled
     *
     * @return bool
     */
    public function isAutoReloadEnabled(): bool
    {
        /** @var int $watchableJobs */
        $watchableJobs = $this->jobCollectionFactory
            ->create()
            ->addFieldToFilter('status', ['in' => self::SCHEDULING_AND_PROCESSING_STATUS_IDS])
            ->getSize();

        return $watchableJobs > 0 && $this->scopeConfig->getValue(self::IS_AUTO_RELOAD_CONFIG_PATH);
    }

    /**
     * Description getWatchableStatusIds function
     *
     * @return string
     */
    public function getWatchableStatusIds(): string
    {
        return $this->json->serialize(self::SCHEDULING_AND_PROCESSING_STATUS_IDS);
    }
}
