<?php

namespace Akeneo\Connector\Model\Config;

use Magento\Framework\App\Config\Value;

/**
 * Class Version
 *
 * @category  Class
 * @package   Akeneo\Connector\Model\Config
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Version extends Value
{
    /**
     * @var \Magento\Framework\Component\ComponentRegistrarInterface
     */
    protected $componentRegistrar;
    /**
     * @var \Magento\Framework\Filesystem\Directory\ReadFactory
     */
    protected $readFactory;

    /**
     * Version constructor
     *
     * @param \Magento\Framework\Model\Context                             $context
     * @param \Magento\Framework\Registry                                  $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface           $config
     * @param \Magento\Framework\App\Cache\TypeListInterface               $cacheTypeList
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null           $resourceCollection
     * @param \Magento\Framework\Component\ComponentRegistrarInterface     $componentRegistrar
     * @param \Magento\Framework\Filesystem\Directory\ReadFactory          $readFactory
     * @param array                                                        $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection,
        \Magento\Framework\Component\ComponentRegistrarInterface $componentRegistrar,
        \Magento\Framework\Filesystem\Directory\ReadFactory $readFactory,
        array $data = []
    ) {
        $this->componentRegistrar = $componentRegistrar;
        $this->readFactory        = $readFactory;
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
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getModuleVersion()
    {
        /** @var string $version */
        $version = '0.0.0';
        /** @var null|string $path */
        $path = $this->componentRegistrar->getPath(
            \Magento\Framework\Component\ComponentRegistrar::MODULE,
            'Akeneo_Connector'
        );
        /** @var ReadInterface $directoryRead */
        $directoryRead = $this->readFactory->create($path);
        /** @var string $composerJsonData */
        $composerJsonData = $directoryRead->readFile('composer.json');
        /** @var string[] $data */
        $data = json_decode($composerJsonData);
        if (!empty($data->version)) {
            $version = $data->version;
        }
        return $version;
    }

    /**
     * Inject current installed module version as the config value.
     *
     * @return void
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function afterLoad()
    {
        /** @var string $version */
        $version = $this->getModuleVersion();
        $this->setValue($version);
    }
}
