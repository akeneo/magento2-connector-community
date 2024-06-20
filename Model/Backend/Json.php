<?php

declare(strict_types=1);

namespace Akeneo\Connector\Model\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\App\Config\Value;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Json extends Value
{
    /**
     * This variable contains a JsonSerializer
     *
     * @var JsonSerializer $jsonSerializer
     */
    protected $jsonSerializer;

    /**
     * Json constructor.
     *
     * @param Context               $context
     * @param Registry              $registry
     * @param ScopeConfigInterface  $config
     * @param TypeListInterface     $cacheTypeList
     * @param JsonSerializer        $jsonSerializer
     * @param AbstractResource|null $resource
     * @param AbstractDb|null       $resourceCollection
     * @param array                 $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        JsonSerializer $jsonSerializer,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);

        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * Validate the json before saving it
     *
     * @throws ValidatorException
     */
    public function beforeSave()
    {
        /** @var string $json */
        $json = $this->getValue();
        /** @var string $label */
        $label = $this->getData('field_config/label');

        try {
            if ($json) {
                $this->jsonSerializer->unserialize($json);
            }
        } catch (\Exception $exception) {
            throw new ValidatorException(__("%1 is not a valid json", $label));
        }

        parent::beforeSave();
    }
}
