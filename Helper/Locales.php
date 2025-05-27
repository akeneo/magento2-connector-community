<?php

declare(strict_types=1);

namespace Akeneo\Connector\Helper;

use Akeneo\Connector\Helper\Authenticator as Authenticator;
use Exception;

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
     * @throws Exception
     */
    public function getAkeneoLocales()
    {
        $apiClient = $this->authenticator->getAkeneoApiClient();

        $localeApi = $apiClient->getLocaleApi();

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
