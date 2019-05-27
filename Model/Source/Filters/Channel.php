<?php

namespace Akeneo\Connector\Model\Source\Filters;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Magento\Framework\Option\ArrayInterface;
use Akeneo\Connector\Helper\Authenticator;

/**
 * Class Channel
 *
 * @category  Class
 * @package   Akeneo\Connector\Model\Source\Filters
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Channel implements ArrayInterface
{
    /**
     * This variable contains a mixed value
     *
     * @var Authenticator $akeneoAuthenticator
     */
    protected $akeneoAuthenticator;

    /**
     * Family constructor
     *
     * @param Authenticator $akeneoAuthenticator
     */
    public function __construct(
        Authenticator $akeneoAuthenticator
    ) {
        $this->akeneoAuthenticator = $akeneoAuthenticator;
    }

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        /** @var ResourceCursorInterface $channels */
        $channels = $this->getChannels();
        /** @var array $options */
        $options = [];
        foreach ($channels as $channel) {
            $options[] = [
                'label' => $channel['code'],
                'value' => $channel['code'],
            ];
        }

        return $options;
    }

    /**
     * Retrieve the channels from akeneo using the configured API. If the credentials are not configured or are wrong, return an empty array
     *
     * @return ResourceCursorInterface|array
     */
    public function getChannels()
    {
        try {
            /** @var AkeneoPimClientInterface $akeneoClient */
            $akeneoClient = $this->akeneoAuthenticator->getAkeneoApiClient();

            if (!$akeneoClient) {
                return [];
            }

            return $akeneoClient->getChannelApi()->all();

        } catch (\Exception $exception) {
            return [];
        }
    }
}
