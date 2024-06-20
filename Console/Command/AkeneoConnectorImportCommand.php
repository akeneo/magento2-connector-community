<?php

declare(strict_types=1);

namespace Akeneo\Connector\Console\Command;

use Akeneo\Connector\Api\Data\JobInterface;
use Akeneo\Connector\Executor\JobExecutor;
use Akeneo\Connector\Helper\Config as ConfigHelper;
use Akeneo\Connector\Model\JobRepository;
use Akeneo\Pim\ApiClient\Exception\HttpException;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Data\Collection;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class AkeneoConnectorImportCommand extends Command
{
    /**
     * This constant contains a string
     *
     * @var string IMPORT_CODE
     */
    public const IMPORT_CODE = 'code';
    protected $appState;
    protected $configHelper;
    protected $jobExecutor;
    protected $jobRepository;

    /**
     * @param State $appState
     * @param ConfigHelper $configHelper
     * @param JobExecutor $jobExecutor
     * @param JobRepository $jobRepository
     * @param string|null $name
     */
    public function __construct(
        State $appState,
        ConfigHelper $configHelper,
        JobExecutor $jobExecutor,
        JobRepository $jobRepository,
        $name = null
    ) {
        parent::__construct($name);

        $this->appState = $appState;
        $this->configHelper = $configHelper;
        $this->jobExecutor = $jobExecutor;
        $this->jobRepository = $jobRepository;
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('akeneo_connector:import')->setDescription('Import Akeneo data to Adobe Commerce/Magento')->addOption(
            self::IMPORT_CODE,
            null,
            InputOption::VALUE_REQUIRED,
            'Code of import job to run. To run multiple jobs consecutively, use comma-separated import job codes'
        );
    }

    /**
     * @inheritdoc
     *
     * @throws AlreadyExistsException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->appState->setAreaCode(Area::AREA_ADMINHTML);
        } catch (LocalizedException $exception) {
            /** @var string $message */
            $message = __('Area code already set')->getText();
            $output->writeln($message);

            return 1;
        }
        try {
            /** @var string $code */
            $code = $input->getOption(self::IMPORT_CODE);
            if (!$code) {
                $this->usage($output);
            } else {
                $this->jobExecutor->execute($code, $output);
            }
        } catch (HttpException $e) {
            $this->jobExecutor->displayError($e->getMessage());
            $currentJob = $this->jobExecutor->getCurrentJob();
            $this->jobExecutor->setJobStatus(JobInterface::JOB_ERROR, $currentJob);

            return 1;
        }

        return 0;
    }

    /**
     * Print command usage
     *
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function usage(OutputInterface $output)
    {
        /** @var Collection $jobs */
        $jobs = $this->jobRepository->getList();

        // Options
        $this->displayComment((string)__('Options:'), $output);
        $this->displayInfo((string)__('--code'), $output);
        $output->writeln('');

        // Codes
        $this->displayComment((string)__('Available codes:'), $output);
        /** @var JobInterface $job */
        foreach ($jobs as $job) {
            $this->displayInfo($job->getCode(), $output);
        }
        $output->writeln('');

        // Example
        /** @var JobInterface $job */
        $job = $jobs->getFirstItem();
        /** @var string $code */
        $code = $job->getCode();
        if ($code) {
            $this->displayComment((string)__('Example:'), $output);
            $this->displayInfo((string)__('akeneo_connector:import --code=%1', $code), $output);
        }
    }

    /**
     * Display info in console
     *
     * @param string $message
     * @param OutputInterface $output
     *
     * @return void
     */
    public function displayInfo(string $message, OutputInterface $output)
    {
        if (!empty($message)) {
            /** @var string $coloredMessage */
            $coloredMessage = '<info>' . $message . '</info>';
            $output->writeln($coloredMessage);
        }
    }

    /**
     * Display comment in console
     *
     * @param string $message
     * @param OutputInterface $output
     *
     * @return void
     */
    public function displayComment(string $message, OutputInterface $output)
    {
        if (!empty($message)) {
            /** @var string $coloredMessage */
            $coloredMessage = '<comment>' . $message . '</comment>';
            $output->writeln($coloredMessage);
        }
    }

    /**
     * Display error in console
     *
     * @param string $message
     * @param OutputInterface $output
     *
     * @return void
     */
    public function displayError(string $message, OutputInterface $output)
    {
        if (!empty($message)) {
            /** @var string $coloredMessage */
            $coloredMessage = '<error>' . $message . '</error>';
            $output->writeln($coloredMessage);
        }
    }
}
