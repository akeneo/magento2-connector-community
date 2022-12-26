<?php

declare(strict_types=1);

namespace Akeneo\Connector\Model\Config;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Directory\ReadFactory;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Version extends Value
{
    /**
     * @var ComponentRegistrarInterface
     */
    protected $componentRegistrar;
    /**
     * @var ReadFactory
     */
    protected $readFactory;
    /**
     * Description $jsonSerializer field
     *
     * @var Json $jsonSerializer
     */
    protected $jsonSerializer;
    /**
     * Default Version
     *
     * @var string VERSION
     */
    public const VERSION = '0.0.0';

    /**
     * Version constructor
     *
     * @param Context                     $context
     * @param Registry                    $registry
     * @param ScopeConfigInterface        $config
     * @param TypeListInterface           $cacheTypeList
     * @param AbstractResource|null       $resource
     * @param AbstractDb|null             $resourceCollection
     * @param ComponentRegistrarInterface $componentRegistrar
     * @param ReadFactory                 $readFactory
     * @param Json                        $jsonSerializer
     * @param array                       $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        AbstractResource $resource,
        AbstractDb $resourceCollection,
        ComponentRegistrarInterface $componentRegistrar,
        ReadFactory $readFactory,
        Json $jsonSerializer,
        array $data = []
    ) {
        $this->componentRegistrar = $componentRegistrar;
        $this->readFactory        = $readFactory;
        $this->jsonSerializer     = $jsonSerializer;

        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Get current module version from composer.json
     *
     * @return string
     * @throws FileSystemException
     */
    public function getModuleVersion()
    {
        /** @var string|null $path */
        $path = $this->componentRegistrar->getPath(
            ComponentRegistrar::MODULE,
            'Akeneo_Connector'
        );
        /** @var ReadInterface $directoryRead */
        $directoryRead = $this->readFactory->create($path);
        /** @var string $composerJsonData */
        $composerJsonData = $directoryRead->readFile('composer.json');
        /** @var string[] $data */
        $data = $this->jsonSerializer->unserialize($composerJsonData);

        return $data['version'] ?? self::VERSION;
    }

    /**
     * Inject current installed module version as the config value.
     *
     * @return void
     * @throws FileSystemException
     */
    public function afterLoad()
    {
        /** @var string $version */
        $version = $this->getModuleVersion();
        $this->setValue($version);
    }
}
