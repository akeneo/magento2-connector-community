<?php

namespace Akeneo\Connector\Converter;

use Magento\Framework\Controller\Result\Json as ResultJson;
use Magento\Framework\Controller\Result\JsonFactory as ResultJsonFactory;

/**
 * Class ArrayToJsonResponseConverter
 *
 * @category  Class
 * @package   Akeneo\Connector\Converter
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class ArrayToJsonResponseConverter
{

    /**
     * This variable contains a ResultJsonFactory
     *
     * @var ResultJsonFactory $resultJsonFactory
     */
    private $resultJsonFactory;

    /**
     * ArrayToJsonResponseConverter constructor.
     *
     * @param ResultJsonFactory $resultJsonFactory
     */
    public function __construct(
        ResultJsonFactory $resultJsonFactory
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * This function convert an array to json
     *
     * @param array $data
     *
     * @return ResultJson
     */
    public function convert(array $data)
    {
        /** @var ResultJson $resultJson */
        $resultJson = $this->resultJsonFactory->create();

        return $resultJson->setData($data);
    }
}
