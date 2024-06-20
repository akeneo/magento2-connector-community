<?php

declare(strict_types=1);

namespace Akeneo\Connector\Helper;

use Akeneo\Connector\Api\Data\JobInterface;
use Akeneo\Connector\Executor\JobExecutor;
use Akeneo\Connector\Helper\Config as ConfigHelper;
use Akeneo\Connector\Helper\Locales as LocalesHelper;
use Akeneo\Connector\Helper\Store as StoreHelper;
use Akeneo\Connector\Model\Source\Filters\Completeness;
use Akeneo\Connector\Model\Source\Filters\Mode;
use Akeneo\Connector\Model\Source\Filters\Status;
use Akeneo\Connector\Model\Source\Filters\Update;
use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use Akeneo\Pim\ApiClient\Search\SearchBuilderFactory;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class ProductFilters
{
    /**
     * This variable contains a ConfigHelper
     *
     * @var ConfigHelper $configHelper
     */
    protected $configHelper;
    /**
     * This variable contains a StoreHelper
     *
     * @var StoreHelper $storeHelper
     */
    protected $storeHelper;
    /**
     * This variable contains a LocaleHelper
     *
     * @var Akeneo\Connector\Helper\Locales $localesHelper
     */
    protected $localesHelper;
    /**
     * This variable contains a SearchBuilderFactory
     *
     * @var SearchBuilderFactory $searchBuilderFactory
     */
    protected $searchBuilderFactory;
    /**
     * This variable contains a SearchBuilder
     *
     * @var SearchBuilder $searchBuilder
     */
    protected $searchBuilder;
    /**
     * This variable contains a DateTime
     *
     * @var DateTime $date
     */
    protected $date;
    /**
     * This variable contains a TimezoneInterface
     *
     * @var TimezoneInterface $timezone
     */
    protected $timezone;
    /**
     * Description $json field
     *
     * @var SerializerInterface $json
     */
    protected SerializerInterface $json;

    /**
     * @param Config $configHelper
     * @param Store $storeHelper
     * @param Locales $localesHelper
     * @param SearchBuilderFactory $searchBuilderFactory
     * @param DateTime $date
     * @param TimezoneInterface $timezone
     * @param SerializerInterface $json
     */
    public function __construct(
        ConfigHelper $configHelper,
        StoreHelper $storeHelper,
        LocalesHelper $localesHelper,
        SearchBuilderFactory $searchBuilderFactory,
        DateTime $date,
        TimezoneInterface $timezone,
        SerializerInterface $json
    ) {
        $this->configHelper = $configHelper;
        $this->storeHelper = $storeHelper;
        $this->localesHelper = $localesHelper;
        $this->searchBuilderFactory = $searchBuilderFactory;
        $this->date = $date;
        $this->timezone = $timezone;
        $this->json = $json;
    }

    /**
     * Get the filters for the product API query
     *
     * @param JobExecutor $jobExecutor
     * @param string|null $productFamily
     * @param bool        $isProductModel
     *
     * @return mixed[]|string[]
     */
    public function getFilters($jobExecutor, $productFamily = null, $isProductModel = false)
    {
        /** @var mixed[] $mappedChannels */
        $mappedChannels = $this->configHelper->getMappedChannels();
        if (empty($mappedChannels)) {
            /** @var string[] $error */
            $error = [
                'error' => __('No website/channel mapped. Please check your configurations.'),
            ];

            return $error;
        }

        /** @var mixed[] $filters */
        $filters = [];
        /** @var mixed[] $search */
        $search = [];
        /** @var string $mode */
        $mode = $this->configHelper->getFilterMode();
        if ($mode == Mode::ADVANCED) {
            /** @var mixed[] $advancedFilters */
            $advancedFilters = $this->getAdvancedFilters($isProductModel);
            // If product import gave a family, add it to the filter
            if ($productFamily) {
                if (isset($advancedFilters['search']['family'])) {
                    unset($advancedFilters['search']['family']);
                }
                /** @var string[] $familyFilter */
                $familyFilter                          = ['operator' => 'IN', 'value' => [$productFamily]];
                $advancedFilters['search']['family'][] = $familyFilter;
            }

            $updatedFilter = $this->getUpdatedFilter($jobExecutor);
            if (!empty($updatedFilter)) {
                $advancedFilters['search']['updated'][0] = $updatedFilter;
            }

            return [$advancedFilters];
        }

        if ($mode == Mode::STANDARD) {
            $this->searchBuilder = $this->searchBuilderFactory->create();
            $this->addCompletenessFilter();
            $this->addStatusFilter();
            $this->addUpdatedFilter($jobExecutor);
            $search = $this->searchBuilder->getFilters();
        }

        // If import product gave a family, add this family to the search
        if ($productFamily) {
            $familyFilter       = ['operator' => 'IN', 'value' => [$productFamily]];
            $search['family'][] = $familyFilter;
        }

        /** @var string[] $akeneoLocales */
        $akeneoLocales = $this->localesHelper->getAkeneoLocales();

        /** @var string $channel */
        foreach ($mappedChannels as $channel) {
            /** @var string[] $filter */
            $filter = [
                'search' => $search,
                'scope'  => $channel,
            ];

            if ($this->configHelper->getCompletenessTypeFilter() !== Completeness::NO_CONDITION) {
                /** @var string[] $completeness */
                $completeness = reset($search['completeness']);
                if (!empty($completeness['scope']) && $completeness['scope'] !== $channel) {
                    $completeness['scope']  = $channel;
                    $search['completeness'] = [$completeness];

                    $filter['search'] = $search;
                }
            }

            if ($this->configHelper->getAttributeFilterByCodeMode() == true) {
                $filter['attributes'] = $this->configHelper->getAttributeFilterByCode();
            }

            /** @var string[] $locales */
            $locales = $this->storeHelper->getChannelStoreLangs($channel);
            if (!empty($locales)) {
                if (!empty($akeneoLocales)) {
                    $locales = array_intersect($locales, $akeneoLocales);
                }

                /** @var string $locales */
                $locales           = implode(',', $locales);
                $filter['locales'] = $locales;
            }

            $filters[] = $filter;
        }

        return $filters;
    }

    /**
     * Get the filters for the product model API query
     *
     * @return mixed[]|string[]
     */
    public function getModelFilters()
    {
        /** @var mixed[] $mappedChannels */
        $mappedChannels = $this->configHelper->getMappedChannels();
        if (empty($mappedChannels)) {
            /** @var string[] $error */
            $error = [
                'error' => __('No website/channel mapped. Please check your configurations.'),
            ];

            return $error;
        }

        /** @var mixed[] $filters */
        $filters = [];

        /** @var string $channel */
        foreach ($mappedChannels as $channel) {
            /** @var string[] $filter */
            $filter = [
                'scope' => $channel,
            ];

            /** @var string[] $locales */
            $locales = $this->storeHelper->getChannelStoreLangs($channel);
            if (!empty($locales)) {
                /** @var string $locales */
                $akeneoLocales = $this->localesHelper->getAkeneoLocales();
                if (!empty($akeneoLocales)) {
                    $locales = array_intersect($locales, $akeneoLocales);
                }

                /** @var string $locales */
                $locales           = implode(',', $locales);
                $filter['locales'] = $locales;
            }

            $filters[] = $filter;
        }

        return $filters;
    }

    /**
     * Retrieve advanced filters config
     *
     * @param bool $isProductModel
     *
     * @return mixed[]
     */
    protected function getAdvancedFilters($isProductModel = false)
    {
        if ($isProductModel) {
            /** @var mixed[] $filters */
            $filters = $this->configHelper->getModelAdvancedFilters();

            return $filters;
        }
        /** @var mixed[] $filters */
        $filters = $this->configHelper->getAdvancedFilters();

        return $filters;
    }

    /**
     * Add completeness filter for Akeneo API
     *
     * @return void
     */
    protected function addCompletenessFilter()
    {
        /** @var string $filterType */
        $filterType = $this->configHelper->getCompletenessTypeFilter();
        if ($filterType === Completeness::NO_CONDITION) {
            return;
        }

        /** @var string $scope */
        $scope = $this->configHelper->getAdminDefaultChannel();
        /** @var mixed[] $options */
        $options = ['scope' => $scope];

        /** @var string $filterValue */
        $filterValue = $this->configHelper->getCompletenessValueFilter();

        /** @var string[] $localesType */
        $localesType = [
            Completeness::LOWER_OR_EQUALS_THAN_ON_ALL_LOCALES,
            Completeness::LOWER_THAN_ON_ALL_LOCALES,
            Completeness::GREATER_THAN_ON_ALL_LOCALES,
            Completeness::GREATER_OR_EQUALS_THAN_ON_ALL_LOCALES,
        ];
        if (in_array($filterType, $localesType)) {
            /** @var mixed $locales */
            $locales = $this->configHelper->getCompletenessLocalesFilter();
            /** @var string[] $locales */
            $locales            = explode(',', $locales ?? '');
            $options['locales'] = $locales;
        }

        $this->searchBuilder->addFilter('completeness', $filterType, $filterValue, $options);
    }

    /**
     * Add status filter for Akeneo API
     *
     * @return void
     */
    protected function addStatusFilter()
    {
        /** @var string $filter */
        $filter = $this->configHelper->getStatusFilter();
        if ($filter === Status::STATUS_NO_CONDITION) {
            return;
        }
        $this->searchBuilder->addFilter('enabled', '=', (bool)$filter);
    }

    /**
     * Get updated filter Data for Akeneo API
     *
     * @param JobExecutor $jobExecutor
     *
     * @return mixed[]
     */
    protected function getUpdatedFilter($jobExecutor)
    {
        /** @var string $mode */
        $mode = $this->configHelper->getUpdatedMode();

        if ($mode == Update::BETWEEN) {
            $dateAfter  = $this->configHelper->getUpdatedBetweenAfterFilter() . ' 00:00:00';
            $dateBefore = $this->configHelper->getUpdatedBetweenBeforeFilter() . ' 23:59:59';
            if (empty($dateAfter) || empty($dateBefore)) {
                return [];
            }
            $dates = [$dateAfter, $dateBefore];

            return [
                'operator' => $mode,
                'value' => $dates,
            ];
        }
        if ($mode == Update::SINCE_LAST_N_DAYS) {
            /** @var string $filter */
            $filter = $this->configHelper->getUpdatedSinceFilter();
            if (!is_numeric($filter)) {
                return [];
            }

            return [
                'operator' => $mode,
                'value' => (int)$filter,
            ];
        }
        if ($mode == Update::SINCE_LAST_IMPORT) {
            // Get the last import date as filter
            /** @var string $filter */
            $filter = $this->getLastImportDateFilter($jobExecutor);
            if (!$filter) {
                return [];
            }

            return [
                'operator' => Update::GREATER_THAN,
                'value' => $filter,
            ];
        }
        if ($mode == Update::SINCE_LAST_N_HOURS) {
            /** @var int $currentDateTime */
            $currentDateTime = $this->timezone->date()->getTimestamp();
            /** @var string $valueConfig */
            $valueConfig = $this->configHelper->getUpdatedSinceLastHoursFilter();
            if (!$valueConfig) {
                return [];
            }
            /** @var int $filter */
            $filter = ((int)$valueConfig) * 3600;
            if (!is_numeric($filter)) {
                return [];
            }

            /** @var int $timestamp */
            $timestamp = $currentDateTime - $filter;
            /** @var string $date */
            $date = (new \DateTime())->setTimestamp($timestamp)->format('Y-m-d H:i:s');

            if (!empty($date)) {
                return [
                    'operator' => Update::GREATER_THAN,
                    'value' => $date,
                ];
            }

            return [];
        }
        if ($mode == Update::LOWER_THAN) {
            /** @var string $date */
            $date = $this->configHelper->getUpdatedLowerFilter();
            if (empty($date)) {
                return [];
            }
            $date = $date . ' 23:59:59';
        }
        if ($mode == Update::GREATER_THAN) {
            $date = $this->configHelper->getUpdatedGreaterFilter();
            if (empty($date)) {
                return [];
            }
            $date = $date . ' 00:00:00';
        }
        if (!empty($date)) {
            return [
                'operator' => $mode,
                'value' => $date,
            ];
        }
    }

    /**
     * Add updated filter for Akeneo API
     *
     * @param JobExecutor $jobExecutor
     *
     * @return void
     */
    protected function addUpdatedFilter($jobExecutor)
    {
        $updatedFilter = $this->getUpdatedFilter($jobExecutor);

        if (empty($updatedFilter)) {
            return;
        }

        $this->searchBuilder->addFilter('updated', $updatedFilter['operator'], $updatedFilter['value']);
    }

    /**
     * Returning last import date filter, return a string for all job but product one return a json with multiple value, one for each family
     * Fallback to a default value which fallback on null
     *
     * @param JobExecutor $jobExecutor
     *
     * @return string|null
     */
    protected function getLastImportDateFilter($jobExecutor)
    {
        /** @var JobInterface $currentJob */
        $currentJob = $jobExecutor->getCurrentJob();
        /** @var string|null $lastSuccessExecutedDate */
        $lastSuccessExecutedDate = $jobExecutor->getCurrentJob()->getLastSuccessExecutedDate();

        if (!isset($lastSuccessExecutedDate) || $currentJob->getCode() !== JobExecutor::IMPORT_CODE_PRODUCT) {
            return $lastSuccessExecutedDate;
        }

        /** @var string[] $lastSuccessExecutedDateData */
        $lastSuccessExecutedDateData = $this->json->unserialize($lastSuccessExecutedDate);
        /** @var string $currentFamilyCode */
        $currentFamilyCode = $jobExecutor->getCurrentJobClass()->getFamily();

        if (isset($lastSuccessExecutedDateData[$currentFamilyCode])) {
            return $lastSuccessExecutedDateData[$currentFamilyCode];
        }
        return $lastSuccessExecutedDateData[JobInterface::DEFAULT_PRODUCT_JOB_FAMILY_CODE] ?? null;

    }
}
