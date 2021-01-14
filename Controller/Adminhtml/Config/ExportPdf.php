<?php

declare(strict_types=1);

namespace Akeneo\Connector\Controller\Adminhtml\Config;

use Akeneo\Connector\Helper\Config;
use Akeneo\Connector\Model\Source\Edition;
use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Db\Select;
use Zend_Pdf;
use Zend_Pdf_Exception;
use Zend_Pdf_Font;
use Zend_Pdf_Page;
use Zend_Pdf_Resource_Font;
use Zend_Pdf_Style;

/**
 * Class ExportPdf
 *
 * @package   Akeneo\Connector\Controller\Adminhtml\Config
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
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
     * Description $resourceConnection field
     *
     * @var ResourceConnection $resourceConnection
     */
    protected $resourceConnection;
    /**
     * Description $sourceEdition field
     *
     * @var Edition $sourceEdition
     */
    protected $sourceEdition;
    /**
     * Description HIDDEN_FIELDS constant
     *
     * @var string[] HIDDEN_FIELDS
     */
    const HIDDEN_FIELDS = [
        Config::AKENEO_API_BASE_URL,
        Config::AKENEO_API_PASSWORD,
        Config::AKENEO_API_USERNAME,
        Config::AKENEO_API_CLIENT_ID,
        Config::AKENEO_API_CLIENT_SECRET,
    ];

    /**
     * ExportPdf constructor
     *
     * @param Context            $context
     * @param FileFactory        $fileFactory
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        Context $context,
        FileFactory $fileFactory,
        ResourceConnection $resourceConnection
    ) {
        $this->fileFactory        = $fileFactory;
        $this->resourceConnection = $resourceConnection;

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
        /** @var Zend_Pdf $pdf */
        $pdf = new Zend_Pdf();
        /** @var Zend_Pdf_Page $page */
        $page         = $pdf->newPage(Zend_Pdf_Page::SIZE_A4);
        $pdf->pages[] = $page;
        /** @var string $fileName */
        $fileName = 'export.pdf';

        /** @var AdapterInterface $connection */
        $connection = $this->resourceConnection->getConnection();
        /** @var Select $select */
        $select = $connection->select()->from(
            [
                'ccd' => 'core_config_data',
            ]
        )->where('path like ?', '%akeneo%');

        /** @var mixed[] $configs */
        $configs = $connection->fetchAll($select);

        $this->setPageStyle($page);
        /** @var int $lastPosition */
        $lastPosition = 710;

        /**
         * @var int      $index
         * @var string[] $config
         */
        foreach ($configs as $index => $config) {
            /** @var string $value */
            $value = $config['path'] . ' : ';

            if (in_array($config['path'], self::HIDDEN_FIELDS)) {
                $value .= '****';
            } else {
                $value .= $config['value'];
            }

            if($config['path'] === Config::AKENEO_API_EDITION) {
                $value = $this->getEdition();
            }

            $page->drawText($value, 100, $lastPosition);
            $lastPosition -= 10;
        }

        return $this->fileFactory->create(
            $fileName,
            $pdf->render(),
            DirectoryList::VAR_DIR,
            'application/pdf'
        );
    }

    /**
     * Description setPageStyle function
     *
     * @param Zend_Pdf_Page $page
     *
     * @return mixed
     * @throws Zend_Pdf_Exception
     */
    protected function setPageStyle($page)
    {
        /** @var Zend_Pdf_Style $style */
        $style = new Zend_Pdf_Style();
        $style->setLineColor(new \Zend_Pdf_Color_Rgb(0, 0, 0));
        /** @var Zend_Pdf_Resource_Font $font */
        $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_TIMES);
        $style->setFont($font, 5);
        $page->setStyle($style);

        return $page;
    }

    /**
     * Description getEdition function
     *
     * @return string
     */
    protected function getEdition()
    {
        $version = '';
        return $version;
    }
}
