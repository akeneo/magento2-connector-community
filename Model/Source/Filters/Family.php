<?php

namespace Akeneo\Connector\Model\Source\Filters;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Magento\Framework\Option\ArrayInterface;
use Akeneo\Connector\Helper\Authenticator;

/**
 * Class Family
 *
 * @category  Class
 * @package   Akeneo\Connector\Model\Source\Filters
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Family implements ArrayInterface
{
    /**
     * This variable contains a mixed value
     *
     * @var Authenticator $akeneoAuthenticator
     */
    protected $akeneoAuthenticator;
    /**
     * Description $logger field
     *
     * @var \Psr\Log\LoggerInterface $logger
     */
    protected $logger;

    /**
     * Family constructor
     *
     * @param Authenticator $akeneoAuthenticator
     */
    public function __construct(
        Authenticator $akeneoAuthenticator,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->akeneoAuthenticator = $akeneoAuthenticator;
        $this->logger              = $logger;
    }

    /**
     * Initialize options
     *
     * @return void
     */
    public function getFamilies()
    {
        /** @var array $families */
        $families = [];

        try {
            /** @var AkeneoPimClientInterface $client */
            $client = $this->akeneoAuthenticator->getAkeneoApiClient();

            if (empty($client)) {
                return $families;
            }

            /** @var ResourceCursorInterface $families */
            $akeneoFamilies = $client->getFamilyApi()->all();
            /** @var mixed[] $family */
            foreach ($akeneoFamilies as $family) {
                if (!isset($family['code'])) {
                    continue;
                }
                $families[$family['code']] = $family['code'];
            }
        } catch (\Exception $exception) {
            $this->logger->warning($exception->getMessage());
        }

        return $families;
    }

    /**
     * Retrieve options value and label in an array
     *
     * @return array
     */
    public function toOptionArray()
    {
        /** @var array $families */
        $families = $this->getFamilies();
        /** @var array $optionArray */
        $optionArray = [];

        /** check if family is empty */
        if(empty($families)){
            return $optionArray;
        }

        /**
         * @var int    $optionValue
         * @var string $optionLabel
         */
        foreach ($families as $optionValue => $optionLabel) {
            $optionArray[] = [
                'value' => $optionValue,
                'label' => $optionLabel,
            ];
        }

        return $optionArray;
    }
}
