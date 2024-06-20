<?php

declare(strict_types=1);

namespace Akeneo\Connector\Setup\Patch\Data;

use Akeneo\Connector\Helper\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class EncryptApiSecret implements DataPatchInterface, PatchVersionInterface
{
    /**
     * $scopeConfig field
     *
     * @var ScopeConfigInterface $scopeConfig
     */
    private $scopeConfig;
    /**
     * $resourceConfig field
     *
     * @var ConfigInterface $resourceConfig
     */
    private $resourceConfig;
    /**
     * $encryptor field
     *
     * @var EncryptorInterface $encryptor
     */
    private $encryptor;

    /**
     * EncryptApiSecret constructor
     *
     * @param ConfigInterface      $resourceConfig
     * @param EncryptorInterface   $encryptor
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ConfigInterface $resourceConfig,
        EncryptorInterface $encryptor,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->resourceConfig = $resourceConfig;
        $this->encryptor      = $encryptor;
        $this->scopeConfig    = $scopeConfig;
    }

    /**
     * Description apply function
     *
     * @return void
     */
    public function apply(): void
    {
        /** @var string|null $unencryptedSecret */
        $unencryptedSecret = $this->scopeConfig->getValue(Config::AKENEO_API_CLIENT_SECRET);
        if ($unencryptedSecret) {
            /** @var string $encryptedSecret */
            $encryptedSecret = $this->encryptor->encrypt($unencryptedSecret);
            $this->resourceConfig->saveConfig(Config::AKENEO_API_CLIENT_SECRET, $encryptedSecret);
        }
    }

    /**
     * Description getDependencies function
     *
     * @return array
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Description getAliases function
     *
     * @return array
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * Description getVersion function
     *
     * @return string
     */
    public static function getVersion(): string
    {
        return '1.0.6';
    }
}
