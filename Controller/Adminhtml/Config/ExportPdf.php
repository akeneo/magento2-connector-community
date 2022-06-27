<?php

declare(strict_types=1);

namespace Akeneo\Connector\Controller\Adminhtml\Config;

use Akeneo\Connector\Model\Config\ConfigManagement;
use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Zend_Pdf_Exception;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class ExportPdf extends Action
{
    /**
     * Description $fileFactory field
     *
     * @var FileFactory $fileFactory
     */
    protected $fileFactory;
    /**
     * Description $configManagement field
     *
     * @var ConfigManagement $configManagement
     */
    protected $configManagement;
    /**
     * Description FILENAME constant
     *
     * @var string FILENAME
     */
    public const FILENAME = "Akeneo Connector - Configuration Export.pdf";

    /**
     * ExportPdf constructor
     *
     * @param Context          $context
     * @param FileFactory      $fileFactory
     * @param ConfigManagement $configManagement
     */
    public function __construct(
        Context $context,
        FileFactory $fileFactory,
        ConfigManagement $configManagement
    ) {
        $this->fileFactory      = $fileFactory;
        $this->configManagement = $configManagement;

        parent::__construct($context);
    }

    /**
     * Description execute function
     *
     * @return ResponseInterface
     * @throws Zend_Pdf_Exception
     * @throws Exception
     */
    public function execute()
    {
        /** @var string $fileName */
        $fileName = self::FILENAME;

        $pdf = $this->configManagement->generatePdf();

        return $this->fileFactory->create(
            $fileName,
            $pdf->render(),
            DirectoryList::VAR_DIR,
            'application/pdf'
        );
    }
}
