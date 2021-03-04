<?php

declare(strict_types=1);

namespace Akeneo\Connector\Model;

use Akeneo\Pim\ApiClient\AkeneoPimClientBuilder;
use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientBuilder;
use Magento\Framework\ObjectManagerInterface;

class AkeneoPimClientBuilderFactory
{
    /** @var ObjectManagerInterface $objectManager */
    private $objectManager = null;

    /** @var string $instanceName */
    private $instanceName = null;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param string $instanceName
     */
    public function __construct(ObjectManagerInterface $objectManager, $instanceName = AkeneoPimClientBuilder::class)
    {
        $this->objectManager = $objectManager;
        $this->instanceName = $instanceName;
    }

    /**
     * Create class instance with specified parameters.
     *
     * @param array $data
     *
     * @return AkeneoPimClientBuilder|AkeneoPimEnterpriseClientBuilder
     */
    public function create(array $data = [])
    {
        return $this->objectManager->create($this->instanceName, $data);
    }
}
