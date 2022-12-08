<?php

declare(strict_types=1);

namespace Akeneo\Connector\Helper;

use Akeneo\Connector\Helper\Authenticator as Authenticator;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Locales
{
    /**
     * This variable contains a Authenticator
     *
     * @var Authenticator $authenticator
     */
    protected $authenticator;

    /**
     * Locales constructor
     *
     * @param Authenticator $authenticator
     */
    public function __construct(
        Authenticator $authenticator
    ) {
        $this->authenticator = $authenticator;
    }

    /**
     * Get active Akeneo locales
     *
     * @return string[]
     * @throws Akeneo_Connector_Api_Exception
     */
    public function getAkeneoLocales()
    {
        /** @var Akeneo\Pim\ApiClient\AkeneoPimClientInterface $apiClient */
        $apiClient = $this->authenticator->getAkeneoApiClient();
        /** @var \Akeneo\Pim\ApiClient\Api\LocaleApiInterface $localeApi */
        $localeApi = $apiClient->getLocaleApi();
        /** @var Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface $locales */
        $locales = $localeApi->all(
            10,
            [
                'search' => [
                    'enabled' => [
                        [
                            'operator' => '=',
                            'value'    => true,
                        ],
                    ],
                ],
            ]
        );

        /** @var string[] $akeneoLocales */
        $akeneoLocales = [];
        /** @var mixed[] $locale */
        foreach ($locales as $locale) {
            if (empty($locale['code'])) {
                continue;
            }
            $akeneoLocales[] = $locale['code'];
        }

        return $akeneoLocales;
    }
}
