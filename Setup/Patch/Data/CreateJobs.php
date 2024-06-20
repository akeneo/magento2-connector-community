<?php

declare(strict_types=1);

namespace Akeneo\Connector\Setup\Patch\Data;

use Akeneo\Connector\Api\Data\JobInterface;
use Akeneo\Connector\Job\Attribute;
use Akeneo\Connector\Job\Category;
use Akeneo\Connector\Job\Family;
use Akeneo\Connector\Job\Option;
use Akeneo\Connector\Job\Product;
use Akeneo\Connector\Model\Job;
use Akeneo\Connector\Model\JobFactory;
use Akeneo\Connector\Model\JobRepository;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class CreateJobs implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    protected $moduleDataSetup;
    /**
     * Description $jobRepository field
     *
     * @var JobRepository $jobRepository
     */
    protected $jobRepository;
    /**
     * Description $jobRepository field
     *
     * @var JobFactory $jobFactory
     */
    protected $jobFactory;
    /**
     * Jobs codes ordered
     *
     * @var string[] JOBS_CODES
     */
    protected const JOBS_CODES = [
        'category'  => Category::class,
        'family'    => Family::class,
        'attribute' => Attribute::class,
        'option'    => Option::class,
        'product'   => Product::class,
    ];

    /**
     * CreateJobs constructor
     *
     * @param ModuleDataSetupInterface $dataSetup
     * @param JobRepository            $jobRepository
     * @param JobFactory               $jobFactory
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
     * @return CreateJobs
     * @throws AlreadyExistsException
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $index = 0;
        /**
         * @var string $code
         * @var string $class
         */
        foreach (self::JOBS_CODES as $code => $class) {
            /** @var string $name */
            $name = ucfirst($code);
            /** @var Job $job */
            $job = $this->jobFactory->create();
            $job->setCode($code);
            $job->setPosition($index);
            $job->setStatus(JobInterface::JOB_PENDING);
            $job->setName($name);
            $job->setJobClass($class);
            $this->jobRepository->save($job);
            $index++;
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
