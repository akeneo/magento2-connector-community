<?php

namespace Akeneo\Connector\Model\Source\Filters;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Magento\Framework\Option\ArrayInterface;
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
     * @var \Psr\Log\LoggerInterface $logger
     */
    private $logger;

    /**
     * This variable contains Categories options
     *
     * @var string[] $options
     */
    protected $options = [];

    /**
     * Category constructor
     *
     * @param Authenticator            $akeneoAuthenticator
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        Authenticator $akeneoAuthenticator,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->akeneoAuthenticator = $akeneoAuthenticator;
        $this->logger              = $logger;
        $this->init();
    }

    /**
     * Initialize options
     *
     * @return void
     */
    public function init()
    {
        try {
            /** @var AkeneoPimClientInterface $client */
            $client = $this->akeneoAuthenticator->getAkeneoApiClient();
            if (empty($client)) {
                return;
            }
            /** @var ResourceCursorInterface $categories */
            $categories = $client->getCategoryApi()->all();
            /** @var mixed[] $category */
            foreach ($categories as $category) {
                if (!isset($category['code']) || isset($category['parent'])) {
                    continue;
                }
                $this->options[$category['code']] = $category['code'];
            }
        } catch (\Exception $exception) {
            $this->logger->warning($exception->getMessage());
        }
    }

    /**
     * Retrieve options value and label in an array
     *
     * @return array
     */
    public function toOptionArray()
    {
        /** @var array $optionArray */
        $optionArray = [];

        /**
         * @var int    $optionValue
         * @var string $optionLabel
         */
        foreach ($this->options as $optionValue => $optionLabel) {
            $optionArray[] = [
                'value' => $optionValue,
                'label' => $optionLabel,
            ];
        }

        return $optionArray;
    }
}
