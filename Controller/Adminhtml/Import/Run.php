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

/**
 * Class Run
 *
 * @category  Class
 * @package   Akeneo\Connector\Controller\Adminhtml\Import
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Run extends Action
{

    /**
     * This variable contains an OutputHelper
     *
     * @var OutputHelper $outputHelper
     */
    private $outputHelper;
    /**
     * This variable contains an ImportRepositoryInterface
     *
     * @var ImportRepositoryInterface $importRepository
     */
    private $importRepository;
    /**
     * This variable contains a ArrayToJsonResponseConverter
     *
     * @var ArrayToJsonResponseConverter $arrayToJsonResponseConverter
     */
    private $arrayToJsonResponseConverter;

    /**
     * Run constructor.
     *
     * @param Context $context
     * @param ImportRepositoryInterface $importRepository
     * @param OutputHelper $output
     * @param ArrayToJsonResponseConverter $arrayToJsonResponseConverter
     */
    public function __construct(
        Context $context,
        ImportRepositoryInterface $importRepository,
        OutputHelper $output,
        ArrayToJsonResponseConverter $arrayToJsonResponseConverter
    ) {
        parent::__construct($context);

        $this->outputHelper                 = $output;
        $this->importRepository             = $importRepository;
        $this->arrayToJsonResponseConverter = $arrayToJsonResponseConverter;
    }

    /**
     * Action triggered by request
     *
     * @return Json
     */
    public function execute()
    {
        /** @var RequestInterface $request */
        $request = $this->getRequest();
        /** @var int $step */
        $step = (int)$request->getParam('step');
        /** @var string $code */
        $code = $request->getParam('code');
        /** @var string $identifier */
        $identifier = $request->getParam('identifier');

        /** @var Import $import */
        $import = $this->importRepository->getByCode($code);

        if (!$import) {
            /** @var array $response */
            $response = $this->outputHelper->getNoImportFoundResponse();

            return $this->arrayToJsonResponseConverter->convert($response);
        }

        $import->setIdentifier($identifier)->setStep($step)->setSetFromAdmin(true);

        /** @var array $response */
        $response = $import->execute();

        return $this->arrayToJsonResponseConverter->convert($response);
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Akeneo_Connector::import');
    }
}
