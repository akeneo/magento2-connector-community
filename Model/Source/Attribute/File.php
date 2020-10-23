<?php

namespace Akeneo\Connector\Model\Source\Attribute;

use Akeneo\Connector\Helper\Authenticator;
use Akeneo\Connector\Helper\Config as ConfigHelper;
use Akeneo\Connector\Model\Source\Edition;
use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

/**
 * Class File
 *
 * @package   Akeneo\Connector\Model\Source\Attribute
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class File extends AbstractSource
{
    /**
     * This variable contains a mixed value
     *
     * @var Authenticator $akeneoAuthenticator
     */
    protected $akeneoAuthenticator;
    /**
     * This variable contains a AkeneoPimClientInterface
     *
     * @var AkeneoPimClientInterface $akeneoClient
     */
    protected $akeneoClient;
    /**
     * This variable contains a ConfigHelper
     *
     * @var ConfigHelper $configHelper
     */
    protected $configHelper;

    /**
     * File constructor
     *
     * @param Authenticator $akeneoAuthenticator
     * @param ConfigHelper  $configHelper
     */
    public function __construct(
        Authenticator $akeneoAuthenticator,
        ConfigHelper $configHelper
    ) {
        $this->akeneoAuthenticator = $akeneoAuthenticator;
        $this->configHelper        = $configHelper;
    }

    /**
     * Generate array of all file options from connected akeneo
     *
     * @return array
     */
    public function getAllOptions()
    {
        /** @var ResourceCursorInterface|mixed[] $attributes */
        $attributes = $this->getAttributes();

        if (!$attributes) {
            return $this->_options;
        }

        foreach ($attributes as $attribute) {
            if ($attribute['type'] != 'pim_catalog_file') {
                continue;
            }
            $this->_options[] = ['label' => $attribute['code'], 'value' => $attribute['code']];
        }

        return $this->_options;
    }

    /**
     * Generate cursor interface of pim file list
     *
     * @return ResourceCursorInterface|mixed[]
     */
    public function getAttributes()
    {
        try {
            /** @var AkeneoPimClientInterface $akeneoClient */
            $akeneoClient = $this->akeneoAuthenticator->getAkeneoApiClient();

            if (!$akeneoClient) {
                return [];
            }
            /** @var string|int $paginationSize */
            $paginationSize = $this->configHelper->getPaginationSize();

            /** @var string $edition */
            $edition = $this->configHelper->getEdition();

            if($edition == Edition::FOUR || $edition == Edition::SERENITY) {
                $attributeTypeFilter['search']['type'][] = [
                    'operator' => 'IN',
                    'value'    => [
                        'pim_catalog_file',
                    ],
                ];
                return $akeneoClient->getAttributeApi()->all($paginationSize, $attributeTypeFilter);
            }

            return $akeneoClient->getAttributeApi()->all($paginationSize);
        } catch (\Exception $exception) {
            return [];
        }
    }
}
