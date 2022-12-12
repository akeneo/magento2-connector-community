<?php

declare(strict_types=1);

namespace Akeneo\Connector\Setup\Patch\Data;

use Akeneo\Connector\Api\Data\JobInterface;
use Akeneo\Connector\Executor\JobExecutor;
use Akeneo\Connector\Model\JobRepository;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Class UpdateJobLastSuccessExecutedDateToJson
 *
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class UpdateJobLastSuccessExecutedDateToJson implements DataPatchInterface
{
    /**
     * Module Data Setup
     *
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    protected ModuleDataSetupInterface $moduleDataSetup;
    /**
     * Description $jobRepository field
     *
     * @var JobRepository $jobRepository
     */
    protected JobRepository $jobRepository;
    /**
     * Description $json field
     *
     * @var SerializerInterface $json
     */
    protected SerializerInterface $json;

    /**
     * @param ModuleDataSetupInterface $dataSetup
     * @param JobRepository $jobRepository
     * @param SerializerInterface $json
     */
    public function __construct(
        ModuleDataSetupInterface $dataSetup,
        JobRepository $jobRepository,
        SerializerInterface $json
    ) {
        $this->jobRepository   = $jobRepository;
        $this->moduleDataSetup = $dataSetup;
        $this->json = $json;
    }

    /**
     * Description apply function
     *
     * @return UpdateJobLastSuccessExecutedDateToJson
     */
    public function apply(): UpdateJobLastSuccessExecutedDateToJson
    {
        $this->moduleDataSetup->startSetup();

        /** @var JobInterface $productJob */
        $productJob = $this->jobRepository->getByCode(JobExecutor::IMPORT_CODE_PRODUCT);
        /** @var string|null $lastSuccessExecutedDate */
        $lastSuccessExecutedDate = $productJob->getLastSuccessExecutedDate();

        if(!isset($lastSuccessExecutedDate)) {
            return $this;
        }

        $productJob->setLastSuccessExecutedDate($this->json->serialize(
            [JobInterface::DEFAULT_PRODUCT_JOB_FAMILY_CODE => $lastSuccessExecutedDate]
        ));

        $this->jobRepository->save($productJob);

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
