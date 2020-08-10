<?php

namespace Akeneo\Connector\Model\Source\Filters;

use Akeneo\Connector\Helper\Config as ConfigHelper;
use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Magento\Framework\Option\ArrayInterface;
use Psr\Log\LoggerInterface as Logger;
use Akeneo\Connector\Helper\Authenticator;

/**
 * Class Category
 *
 * @category  Class
 * @package   Akeneo\Connector\Model\Source\Filters
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Category implements ArrayInterface
{
    /**
     * This variable is used for Akeneo Authenticator
     *
     * @var Authenticator $akeneoAuthenticator
     */
    protected $akeneoAuthenticator;
    /**
     *
     *
     * @var Logger $logger
     */
    protected $logger;
    /**
     * This variable contains a ConfigHelper
     *
     * @var ConfigHelper $configHelper
     */
    protected $configHelper;

    /**
     * Category constructor
     *
     * @param Authenticator $akeneoAuthenticator
     * @param Logger        $logger
     * @param ConfigHelper  $configHelper
     */
    public function __construct(
        Authenticator $akeneoAuthenticator,
        Logger $logger,
        ConfigHelper $configHelper
    ) {
        $this->akeneoAuthenticator = $akeneoAuthenticator;
        $this->logger              = $logger;
        $this->configHelper        = $configHelper;
    }

    /**
     * Initialize options
     *
     * @return void
     */
    public function getCategories()
    {
        /** @var array $categories */
        $categories = [];

        try {
            /** @var AkeneoPimClientInterface $client */
            $client = $this->akeneoAuthenticator->getAkeneoApiClient();
            if (empty($client)) {
                return $categories;
            }
            /** @var string|int $paginationSize */
            $paginationSize = $this->configHelper->getPaginationSize();
            /** @var ResourceCursorInterface $categories */
            $akeneoCategories = $client->getCategoryApi()->all($paginationSize);
            /** @var mixed[] $category */
            foreach ($akeneoCategories as $category) {
                if (!isset($category['code']) || isset($category['parent'])) {
                    continue;
                }
                $categories[$category['code']] = $category['code'];
            }
        } catch (\Exception $exception) {
            $this->logger->warning($exception->getMessage());
        }

        return $categories;
    }

    /**
     * Retrieve options value and label in an array
     *
     * @return array
     */
    public function toOptionArray()
    {
        /** @var array $categories */
        $categories = $this->getCategories();
        /** @var array $optionArray */
        $optionArray = [];

        /**
         * @var int    $optionValue
         * @var string $optionLabel
         */
        foreach ($categories as $optionValue => $optionLabel) {
            $optionArray[] = [
                'value' => $optionValue,
                'label' => $optionLabel,
            ];
        }

        return $optionArray;
    }
}
