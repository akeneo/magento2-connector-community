<?php

namespace Akeneo\Connector\Console\Command;

use Akeneo\Connector\Helper\Config as ConfigHelper;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Data\Collection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Akeneo\Connector\Api\ImportRepositoryInterface;
use Akeneo\Connector\Job\Import;
use \Symfony\Component\Console\Command\Command;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;
use \Symfony\Component\Console\Input\InputOption;

/**
 * Class AkeneoConnectorImportCommand
 *
 * @category  Class
 * @package   Akeneo\Connector\Console\Command
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class AkeneoConnectorImportCommand extends Command
{
    /**
     * This constant contains a string
     *
     * @var string IMPORT_CODE
     */
    const IMPORT_CODE = 'code';
    /**
     * This constant contains a string
     *
     * @var string IMPORT_CODE_PRODUCT
     */
    const IMPORT_CODE_PRODUCT = 'product';
    /**
     * This variable contains a State
     *
     * @var State $appState
     */
    protected $appState;
    /**
     * This variable contains a ImportRepositoryInterface
     *
     * @var ImportRepositoryInterface $importRepository
     */
    protected $importRepository;
    /**
     * This variable contains a ConfigHelper
     *
     * @var ConfigHelper $configHelper
     */
    protected $configHelper;

    /**
     * AkeneoConnectorImportCommand constructor
     *
     * @param ImportRepositoryInterface $importRepository
     * @param State                     $appState
     * @param ConfigHelper              $configHelper
     * @param null                      $name
     */
    public function __construct(
        ImportRepositoryInterface $importRepository,
        State $appState,
        ConfigHelper $configHelper,
        $name = null
    ) {
        parent::__construct($name);

        $this->appState         = $appState;
        $this->importRepository = $importRepository;
        $this->configHelper     = $configHelper;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('akeneo_connector:import')->setDescription('Import Akeneo data to Magento')->addOption(
            self::IMPORT_CODE,
            null,
            InputOption::VALUE_REQUIRED,
            'Code of import job to run. To run multiple jobs consecutively, use comma-separated import job codes'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->appState->setAreaCode(Area::AREA_ADMINHTML);
        } catch (LocalizedException $exception) {
            /** @var string $message */
            $message = __('Area code already set')->getText();
            $output->writeln($message);
        }

        /** @var string $code */
        $code = $input->getOption(self::IMPORT_CODE);
        if (!$code) {
            $this->usage($output);
        } else {
            $this->checkEntities($code, $output);
        }
    }

    /**
     * Check if multiple entities have been specified
     * in the command line
     *
     * @param string $code
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function checkEntities(string $code, OutputInterface $output)
    {
        /** @var string[] $entities */
        $entities = explode(',', $code);
        if (count($entities) > 1) {
            $this->multiImport($entities, $output);
        } else {
            $this->import($code, $output);
        }
    }

    /**
     * Run import for multiple entities
     *
     * @param array $entities
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function multiImport(array $entities, OutputInterface $output)
    {
        foreach ($entities as $entity) {
            $this->import($entity, $output);
        }
    }

    /**
     * Run import
     *
     * @param string          $code
     * @param OutputInterface $output
     *
     * @return bool
     */
    protected function import(string $code, OutputInterface $output)
    {
        /** @var Import $import */
        $import = $this->importRepository->getByCode($code);
        if (!$import) {
            /** @var Phrase $message */
            $message = __('Import code not found');
            $this->displayError($message, $output);

            return false;
        }

        if (!$this->configHelper->checkAkeneoApiCredentials()) {
            /** @var Phrase $message */
            $message = __('API credentials are missing. Please configure the connector and retry.');
            $this->displayError($message, $output);

            return false;
        }

        // If product import, run the import once per family
        /** @var array $productFamiliesToImport */
        $productFamiliesToImport = [];
        if ($code == self::IMPORT_CODE_PRODUCT) {
            $productFamiliesToImport = $import->getFamiliesToImport();

            if (!count($productFamiliesToImport)) {
                $message = __('No family to import');
                $this->displayError($message, $output);

                return false;
            }

            foreach ($productFamiliesToImport as $family) {
                $this->runImport($import, $output, $family);
                $import->setIdentifier(null);
            }

            return true;
        }

        // Run the import normaly
        $this->runImport($import, $output);

        return true;
    }

    /**
     * Run the import
     *
     * @param Import     $import
     * @param OutputInterface     $output
     * @param null|string $family
     *
     * @return void
     */
    protected function runImport(Import $import, OutputInterface $output, $family = null) {
        try {
            $import->setStep(0);
            if ($family) {
                $import->setFamily($family);
            }

            while ($import->canExecute()) {
                /** @var string $comment */
                $comment = $import->getComment();
                $this->displayInfo($comment, $output);

                $import->execute();

                /** @var string $message */
                $message = $import->getMessage();
                if (!$import->getStatus()) {
                    $this->displayError($message, $output);
                } else {
                    $this->displayComment($message, $output);
                }

                if ($import->isDone()) {
                    break;
                }
            }
        } catch (\Exception $exception) {
            /** @var string $message */
            $message = $exception->getMessage();
            $this->displayError($message, $output);
        }

        return true;
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
        /** @var Collection $imports */
        $imports = $this->importRepository->getList();

        // Options
        $this->displayComment(__('Options:'), $output);
        $this->displayInfo(__('--code'), $output);
        $output->writeln('');

        // Codes
        $this->displayComment(__('Available codes:'), $output);
        /** @var Import $import */
        foreach ($imports as $import) {
            $this->displayInfo($import->getCode(), $output);
        }
        $output->writeln('');

        // Example
        /** @var Import $import */
        $import = $imports->getFirstItem();
        /** @var string $code */
        $code = $import->getCode();
        if ($code) {
            $this->displayComment(__('Example:'), $output);
            $this->displayInfo(__('akeneo-connector:import --code=%1', $code), $output);
        }
    }

    /**
     * Display info in console
     *
     * @param string          $message
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
     * @param string          $message
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
     * @param string          $message
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
