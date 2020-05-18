<?php

namespace Akeneo\Connector\Controller\Adminhtml\Import;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Akeneo\Connector\Api\ImportRepositoryInterface;
use Akeneo\Connector\Converter\ArrayToJsonResponseConverter;
use Akeneo\Connector\Helper\Output as OutputHelper;
use Akeneo\Connector\Job\Import;
use Akeneo\Connector\Job\Product as JobProduct;

/**
 * Class RunProduct
 *
 * @package   Akeneo\Connector\Controller\Adminhtml\Import
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2020 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class RunProduct extends Action
{
    /**
     * This variable contains an OutputHelper
     *
     * @var OutputHelper $outputHelper
     */
    protected $outputHelper;
    /**
     * This variable contains an ImportRepositoryInterface
     *
     * @var ImportRepositoryInterface $importRepository
     */
    protected $importRepository;
    /**
     * This variable contains a ArrayToJsonResponseConverter
     *
     * @var ArrayToJsonResponseConverter $arrayToJsonResponseConverter
     */
    protected $arrayToJsonResponseConverter;
    /**
     * This variable contains a JobProduct
     *
     * @var JobProduct $jobProduct
     */
    protected $jobProduct;

    /**
     * Run constructor.
     *
     * @param Context                      $context
     * @param ImportRepositoryInterface    $importRepository
     * @param OutputHelper                 $output
     * @param ArrayToJsonResponseConverter $arrayToJsonResponseConverter
     * @param JobProduct                   $jobProduct
     */
    public function __construct(
        Context $context,
        ImportRepositoryInterface $importRepository,
        OutputHelper $output,
        ArrayToJsonResponseConverter $arrayToJsonResponseConverter,
        JobProduct $jobProduct
    ) {
        parent::__construct($context);

        $this->outputHelper                 = $output;
        $this->importRepository             = $importRepository;
        $this->arrayToJsonResponseConverter = $arrayToJsonResponseConverter;
        $this->jobProduct                   = $jobProduct;
    }

    /**
     * Action triggered by request
     *
     * @return Json
     */
    public function execute()
    {
        /** @var string[] $families */
        $families = $this->jobProduct->getFamiliesToImport();
        return $this->arrayToJsonResponseConverter->convert($families);
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Akeneo_Connector::import');
    }
}
