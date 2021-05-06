<?php

declare(strict_types=1);

namespace Akeneo\Connector\Setup\Patch\Data;

use Akeneo\Connector\Api\Data\JobInterface;
use Akeneo\Connector\Helper\Config as ConfigHelper;
use Akeneo\Connector\Model\Job;
use Akeneo\Connector\Model\JobFactory;
use Akeneo\Connector\Model\JobRepository;
use Akeneo\Connector\Model\Source\Status;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Class CreateJobs
 *
 * @package   Akeneo\Connector\Setup\Patch\Data
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class CreateJobs implements DataPatchInterface
{
    /**
     * Module Data Setup
     *
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    protected $moduleDataSetup;
    /**
     * Description $jobRepository field
     *
     * @var \Akeneo\Connector\Model\JobRepository $jobRepository
     */
    protected $jobRepository;
    /**
     * Description $jobRepository field
     *
     * @var \Akeneo\Connector\Model\JobFactory $jobFactory
     */
    protected $jobFactory;
    /**
     * Jobs codes ordered
     *
     * @var string[] JOBS_CODES
     */
    const JOBS_CODES = [
        'category',
        'family',
        'attribute',
        'option',
        'product',
    ];

    /**
     * CreateJobs constructor
     *
     * @param \Magento\Framework\Setup\ModuleDataSetupInterface $dataSetup
     * @param \Akeneo\Connector\Model\JobRepository             $jobRepository
     * @param \Akeneo\Connector\Model\JobFactory                $jobFactory
     */
    public function __construct(
        ModuleDataSetupInterface $dataSetup,
        JobRepository $jobRepository,
        JobFactory $jobFactory
    ) {
        $this->jobRepository   = $jobRepository;
        $this->jobFactory      = $jobFactory;
        $this->moduleDataSetup = $dataSetup;
    }

    /**
     * Description apply function
     *
     * @return \Akeneo\Connector\Setup\Patch\Data\CreateJobs
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        /**
         * @var int    $index
         * @var string $code
         */
        foreach (self::JOBS_CODES as $index => $code) {
            /** @var Job $job */
            $job = $this->jobFactory->create();
            $job->setCode($code);
            $job->setOrder($index);
            $job->setStatus(JobInterface::JOB_PENDING);
            $this->jobRepository->save($job);
        }

        $this->moduleDataSetup->endSetup();

        return $this;
    }

    /**
     * Description getDependencies function
     *
     * @return string[]
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * Description getAliases function
     *
     * @return string[]
     */
    public function getAliases()
    {
        return [];
    }
}
