<?php

namespace Akeneo\Connector\Model\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\ValidatorException;
use Akeneo\Connector\Helper\Serializer as Serializer;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

/**
 * Class Json
 *
 * @category  Class
 * @package   Akeneo\Connector\Model\Backend
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Json extends \Magento\Framework\App\Config\Value
{

    /**
     * This variable contains a json serializer
     *
     * @var Serializer $serializer
     */
    private $serializer;

    /**
     * Json constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param Serializer $serializer
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        Serializer $serializer,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);

        $this->serializer = $serializer;
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
                $this->serializer->unserialize($json);
            }
        } catch (\Exception $exception) {
            throw new ValidatorException(__("%1 is not a valid json", $label));
        }

        parent::beforeSave();
    }
}
