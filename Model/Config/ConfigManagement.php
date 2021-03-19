<?php

declare(strict_types=1);

namespace Akeneo\Connector\Model\Config;

use Akeneo\Connector\Block\Adminhtml\System\Config\Form\Field\Website;
use Akeneo\Connector\Helper\Config as ConfigHelper;
use Akeneo\Connector\Model\Backend\Json;
use Akeneo\Connector\Model\Source\Edition;
use Magento\Config\Model\Config\Backend\Serialized\ArraySerialized;
use Magento\Framework\App\Area;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Module\Dir;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Asset\File;
use Magento\Framework\View\Asset\Repository;
use SimpleXMLElement;
use Zend_Pdf;
use Zend_Pdf_Action_URI;
use Zend_Pdf_Annotation_Link;
use Zend_Pdf_Canvas_Interface;
use Zend_Pdf_Exception;
use Zend_Pdf_Font;
use Zend_Pdf_Image;
use Zend_Pdf_Page;
use Zend_Pdf_Resource_Image;

/**
 * Class ConfigManagement
 *
 * @package   Akeneo\Connector\Model\Config
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class ConfigManagement
{
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
     * Description $moduleReader field
     *
     * @var Reader $moduleReader
     */
    protected $moduleReader;
    /**
     * Description $configHelper field
     *
     * @var ConfigHelper $configHelper
     */
    protected $configHelper;
    /**
     * Description $assetRepository field
     *
     * @var Repository $assetRepository
     */
    protected $assetRepository;
    /**
     * Description $directoryList field
     *
     * @var DirectoryList $directoryList
     */
    protected $directoryList;
    /**
     * Description $serializer field
     *
     * @var SerializerInterface $serializer
     */
    protected $serializer;
    /**
     * Description $websiteFormField field
     *
     * @var Website $websiteFormField
     */
    protected $websiteFormField;
    /**
     * Description HIDDEN_FIELDS constant
     *
     * @var string[] HIDDEN_FIELDS
     */
    const HIDDEN_FIELDS = [
        ConfigHelper::AKENEO_API_BASE_URL,
        ConfigHelper::AKENEO_API_PASSWORD,
        ConfigHelper::AKENEO_API_USERNAME,
        ConfigHelper::AKENEO_API_CLIENT_ID,
        ConfigHelper::AKENEO_API_CLIENT_SECRET,
    ];
    /**
     * Description LINE_BREAK constant
     *
     * @var int LINE_BREAK
     */
    const LINE_BREAK = 20;
    /**
     * Indentation for multiselect list
     *
     * @var int INDENT_MULTISELECT
     */
    const INDENT_MULTISELECT = 120;
    /**
     * Indentation for footer
     *
     * @var int INDENT_FOOTER
     */
    const INDENT_FOOTER = 50;
    /**
     * Indentation for attributes list
     *
     * @var int INDENT_TEXT
     */
    const INDENT_TEXT = 100;
    /**
     * Array line Height
     *
     * @var int ARRAY_LINE_HEIGHT
     */
    const ARRAY_LINE_HEIGHT = 30;
    /**
     * Bottom page border constant
     *
     * @var int BOTTOM_BORDER
     */
    const BOTTOM_PAGE_BORDER = 20;
    /**
     * Description LOGO_PDF constant
     *
     * @var string LOGO_PDF
     */
    const LOGO_PDF = 'Akeneo_Connector::images/logo.jpg';
    /**
     * Description DOCUMENTATION_LINK constant
     *
     * @var string DOCUMENTATION_LINK
     */
    const DOCUMENTATION_LINK = 'https://help.akeneo.com/magento2-connector/v100/articles/download-connector.html#what-can-i-do-if-i-have-a-question-to-ask-a-bug-to-report-or-a-suggestion-to-make-about-the-connector';
    /**
     * Description PASSWORD_CHAR constant
     *
     * @var string PASSWORD_CHAR
     */
    const PASSWORD_CHAR = '****';
    /**
     * Position in Y axis of the last element
     *
     * @var float $lastPosition
     */
    protected $lastPosition = 0;
    /**
     * Current PDF object
     *
     * @var Zend_Pdf $pdf
     */
    protected $pdf;
    /**
     * Current page object
     *
     * @var Zend_Pdf_Page $page
     */
    protected $page;

    /**
     * ConfigManagement constructor
     *
     * @param ResourceConnection  $resourceConnection
     * @param Edition             $sourceEdition
     * @param Reader              $moduleReader
     * @param ConfigHelper        $configHelper
     * @param Repository          $assetRepository
     * @param DirectoryList       $directoryList
     * @param SerializerInterface $serializer
     * @param Website             $websiteFormField
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        Edition $sourceEdition,
        Reader $moduleReader,
        ConfigHelper $configHelper,
        Repository $assetRepository,
        DirectoryList $directoryList,
        SerializerInterface $serializer,
        Website $websiteFormField
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->sourceEdition      = $sourceEdition;
        $this->moduleReader       = $moduleReader;
        $this->configHelper       = $configHelper;
        $this->assetRepository    = $assetRepository;
        $this->directoryList      = $directoryList;
        $this->serializer         = $serializer;
        $this->websiteFormField   = $websiteFormField;
        $this->assetRepository    = $assetRepository;
    }

    /**
     * Description generatePdf function
     *
     * @return Zend_Pdf
     * @throws Zend_Pdf_Exception
     */
    public function generatePdf()
    {
        $this->pdf = new Zend_Pdf();
        $this->addNewPage();

        /** @var mixed[] $configs */
        $configs = $this->getAllAkeneoConfigs();
        /** @var int $configsNumber */
        $configsNumber = count($configs);

        /**
         * @var int      $index
         * @var string[] $config
         */
        foreach ($configs as $index => $config) {
            /** @var string $label */
            $label = $this->getSystemConfigAttribute($config['path'], 'label');
            if (!$label) {
                continue;
            }
            /** @var string $value */
            $value = $label . ' : ';

            // Manage serialized attribute
            if ($this->getSystemConfigAttribute($config['path'], 'backend_model') === ArraySerialized::class) {
                $this->page->drawText($value, self::INDENT_TEXT, $this->lastPosition);

                // Get array labels
                /** @var string[] $configValueUnserialized */
                $configValueUnserialized = $this->serializer->unserialize($config['value']);
                /** @var string[] $firstElement */
                $firstElement = reset($configValueUnserialized);

                if (!$firstElement) {
                    $this->addLineBreak();
                    continue;
                }
                /** @var string[] $firstElementKeys */
                $firstElementKeys = array_keys($firstElement);

                $this->insertSerializedArray(
                    $configValueUnserialized,
                    $firstElementKeys
                );
                continue;
            }

            // Advanced filter field management
            if ($this->getSystemConfigAttribute($config['path'], 'backend_model') === Json::class) {
                /** @var string $text */
                $text = $config['value'];
                /** @var string $cleanValue */
                $cleanValue = preg_replace("/<br>|\n/", "", $text);
                /** @var string[] $lines */
                $lines = str_split($cleanValue, 89);

                $this->page->drawText($value, self::INDENT_TEXT, $this->lastPosition);
                $this->addLineBreak(self::LINE_BREAK);
                if(!$cleanValue) {
                    continue;
                }
                /** @var string $line */
                foreach ($lines as $line) {
                    $this->page->drawText($line, self::INDENT_TEXT, $this->lastPosition);
                    $this->addLineBreak(self::LINE_BREAK);
                }
                continue;
            }

            if ($config['value'] && strpos($config['value'], ',') && !$this->getSystemConfigAttribute(
                    $config['path'],
                    'backend_model'
                )) {
                $this->page->drawText($value, self::INDENT_TEXT, $this->lastPosition);
                $this->insertMultiselect($config['value']);
                continue;
            }

            if (in_array($config['path'], self::HIDDEN_FIELDS) && $config['value']) {
                $value .= self::PASSWORD_CHAR;
            } else {
                $value .= $config['value'];
            }

            if ($config['path'] === ConfigHelper::AKENEO_API_EDITION) {
                $value = $label . ' : ' . $this->getEdition();
            }

            $this->page->drawText($value, self::INDENT_TEXT, $this->lastPosition);

            if ($index === $configsNumber - 1) {
                $this->addLineBreak(50, self::LINE_BREAK);
                $this->addFooter();
                continue;
            }

            $this->addLineBreak(10);
        }

        return $this->pdf;
    }

    /**
     * Description setPageStyle function
     *
     * @return void
     * @throws Zend_Pdf_Exception
     */
    protected function setPageStyle()
    {
        $style = new \Zend_Pdf_Style();
        $style->setLineColor(new \Zend_Pdf_Color_Rgb(0, 0, 0));
        $font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
        $style->setFont($font, 10);
        $this->page->setStyle($style);
    }

    /**
     * Insert multiselect into the pdf
     *
     * @param string $values
     *
     * @return void
     * @throws Zend_Pdf_Exception
     */
    protected function insertMultiselect($values)
    {
        /** @var string[] $valuesArray */
        $valuesArray = explode(',', $values);
        /** @var string $value */
        foreach ($valuesArray as $value) {
            $this->addLineBreak(self::LINE_BREAK);
            $this->page->drawText('- ' . $value, self::INDENT_MULTISELECT, $this->lastPosition);
        }

        $this->addLineBreak();
    }

    /**
     * Description insertSerializedArray function
     *
     * @param string[] $values
     * @param string[] $headers
     *
     * @return void
     * @throws Zend_Pdf_Exception
     */
    protected function insertSerializedArray(array $values, array $headers)
    {
        /** @var float $maxLengthValue */
        $maxLengthValue = $this->getMaxLengthValue($values);

        /** @var float $rowLength */
        $rowLength = ($maxLengthValue * count($headers)) + 10;
        /** @var float $cellLength */
        $cellLength = $rowLength / count($headers);

        $this->addLineBreak(self::ARRAY_LINE_HEIGHT);
        $this->addArrayRow($headers, $cellLength, $rowLength);
        // Footer detection
        $this->addLineBreak(self::ARRAY_LINE_HEIGHT, 0);

        /** @var string[] $value */
        foreach ($values as $value) {
            // Delete all keys of the array
            /** @var string[] $arrayValues */
            $arrayValues = [];
            /** @var string $attribute */
            foreach ($value as $attribute) {
                $arrayValues[] = $attribute;
            }
            $this->addArrayRow($arrayValues, $cellLength, $rowLength);
        }

        $this->addLineBreak(self::ARRAY_LINE_HEIGHT);
    }

    /**
     * Description insertHeader function
     *
     * @param Zend_Pdf_Page $page
     *
     * @return void
     * @throws Zend_Pdf_Exception
     */
    protected function insertHeader(Zend_Pdf_Page $page)
    {
        /** @var string $title */
        $title = (string)__('Akeneo Connector for Magento 2 - Configuration export');
        /** @var float $titleLength */
        $titleLength = $this->widthForStringUsingFontSize($title);
        $page->drawText($title, ($page->getWidth() - $titleLength) / 2, $this->lastPosition);

        $this->addLineBreak();

        /** @var string $fileId */
        $fileId = self::LOGO_PDF;
        /** @var string[] $params */
        $params = [
            Area::PARAM_AREA => Area::AREA_ADMINHTML,
        ];
        /** @var File $asset */
        $asset = $this->assetRepository->createAsset($fileId, $params);
        /** @var string $imageFullPath */
        $imageFullPath = $asset->getSourceFile();
        /** @var Zend_Pdf_Resource_Image $image */
        $image = Zend_Pdf_Image::imageWithPath($imageFullPath);

        /** @var mixed $imageWidth */
        $imageWidth = $image->getPixelWidth();
        /** @var mixed $imageHeigth */
        $imageHeigth = $image->getPixelHeight();
        /** @var float $x1 */
        $x1 = ($page->getWidth() - $imageWidth) / 2;
        /** @var float $x2 */
        $x2 = $x1 + $imageWidth;
        // Display Logo
        $page->drawImage($image, $x1, $this->lastPosition - $imageHeigth, $x2, $this->lastPosition);

        $this->addLineBreak(0, $imageHeigth);
    }

    /**
     * Description addNewPage function
     *
     * @return void
     * @throws Zend_Pdf_Exception
     */
    protected function addNewPage()
    {
        $this->page         = $this->pdf->newPage(Zend_Pdf_Page::SIZE_A4);
        $this->pdf->pages[] = $this->page;
        $this->setPageStyle();

        $this->lastPosition = $this->page->getHeight() - self::LINE_BREAK;

        if (count($this->pdf->pages) === 1) {
            $this->insertHeader($this->page);
        }
    }

    /**
     * Description getEdition function
     *
     * @return string
     */
    protected function getEdition()
    {
        /** @var string[] $versions */
        $versions = $this->sourceEdition->toOptionArray();

        return $versions[$this->configHelper->getEdition()];
    }

    /**
     * Description getSystemConfigAttribute function
     *
     * @param string $path
     * @param string $attributeName
     *
     * @return bool|string
     */
    protected function getSystemConfigAttribute(string $path, string $attributeName)
    {
        /** @var string[] $path */
        $path = explode('/', $path);
        /** @var string $etcDir */
        $etcDir = $this->moduleReader->getModuleDir(
            Dir::MODULE_ETC_DIR,
            'Akeneo_Connector'
        );
        /** @var mixed[] $xml */
        $xml = simplexml_load_file($etcDir . '/adminhtml/system.xml');
        /** @var string $label */
        $label = '';

        /** @var SimpleXMLElement $group */
        foreach ($xml->{'system'}->{'section'}->{'group'} as $group) {
            /** @var string[] $attributes */
            $attributes = $group->attributes();
            if ((string)$attributes['id'] === $path[1]) {
                foreach ($group->{'field'} as $field) {
                    /** @var string[] $attributes */
                    $attributes = $field->attributes();
                    if ((string)$attributes['id'] === $path[2]) {
                        $label = $field->{$attributeName};
                    }
                }
            }
        }

        return (string)$label;
    }

    /**
     * Description getAllAkeneoConfigs function
     *
     * @return mixed[]
     */
    protected function getAllAkeneoConfigs()
    {
        /** @var AdapterInterface $connection */
        $connection = $this->resourceConnection->getConnection();
        /** @var Select $select */
        $select = $connection->select()->from(
            [
                'ccd' => 'core_config_data',
            ]
        )->where('path like ?', '%akeneo%');

        return $connection->fetchAll($select);
    }

    /**
     * Description addFooter function
     *
     * @return void
     * @throws Zend_Pdf_Exception
     */
    protected function addFooter()
    {
        /** @var string $text */
        $text = (string)__(
            "If you want to report a bug, ask a question or have a suggestion to make on Akeneo Connector for Magento 2,"
        );
        /** @var string $text2 */
        $text2 = (string)__("please follow this steps to contact our Support Team");

        $this->page->drawText($text, self::INDENT_FOOTER, $this->lastPosition - self::LINE_BREAK);
        $this->page->drawText($text2, self::INDENT_FOOTER, $this->lastPosition - (self::LINE_BREAK) * 2);

        $target     = Zend_Pdf_Action_URI::create(
            self::DOCUMENTATION_LINK
        );
        $annotation = Zend_Pdf_Annotation_Link::create(
            127,
            $this->lastPosition - 30,
            155,
            $this->lastPosition - 30,
            $target
        );
        $this->page->attachAnnotation($annotation);
    }

    /**
     * Description widthForStringUsingFontSize function
     *
     * @param string $string
     *
     * @return float|int
     * @throws Zend_Pdf_Exception
     */
    protected function widthForStringUsingFontSize(string $string)
    {
        $drawingString = iconv('UTF-8', 'UTF-16BE//IGNORE', $string);
        $characters    = [];
        for ($i = 0; $i < strlen($drawingString); $i++) {
            $characters[] = (ord($drawingString[$i++]) << 8) | ord($drawingString[$i]);
        }

        $font   = $this->page->getFont();
        $glyphs = $this->page->getFont()->glyphNumbersForCharacters($characters);
        $widths = $font->widthsForGlyphs($glyphs);

        return (array_sum($widths) / $font->getUnitsPerEm()) * $this->page->getFontSize();
    }

    /**
     * Description getMaxLengthValue function
     *
     * @param string[] $values
     *
     * @return float
     * @throws Zend_Pdf_Exception
     */
    protected function getMaxLengthValue(array $values)
    {
        /** @var float $maxLength */
        $maxLength = 0;
        /** @var string[] $value */
        foreach ($values as $key => $value) {
            foreach ($value as $attributeKey => $attribute) {
                /** @var float $lenth */
                $lengthValue = $this->widthForStringUsingFontSize($attribute);
                $lengthKey   = $this->widthForStringUsingFontSize($attributeKey);
                if ($lengthValue > $maxLength) {
                    $maxLength = $lengthValue;
                }

                if ($lengthKey > $maxLength) {
                    $maxLength = $lengthKey;
                }
            }
        }

        return $maxLength;
    }

    /**
     * Add line break in the page
     *
     * @param float|null $nextElementHeight
     * @param float|null $value
     *
     * @return void
     * @throws Zend_Pdf_Exception
     */
    protected function addLineBreak($nextElementHeight = null, $value = null)
    {
        if (is_null($nextElementHeight)) {
            $nextElementHeight = 0;
        }

        if ($this->lastPosition <= self::BOTTOM_PAGE_BORDER || ($this->lastPosition - $nextElementHeight <= self::BOTTOM_PAGE_BORDER)) {
            $this->addNewPage();
        }

        if (is_null($value)) {
            $this->lastPosition -= self::LINE_BREAK;
        } else {
            $this->lastPosition -= $value;
        }
    }

    /**
     * Description addArrayLine function
     *
     * @param string[] $values
     * @param float    $cellLength
     * @param float    $rowLength
     *
     * @return void
     * @throws Zend_Pdf_Exception
     */
    protected function addArrayRow(
        array $values,
        float $cellLength,
        float $rowLength
    ) {
        /** @var Zend_Pdf_Canvas_Interface $line */
        $this->page->drawRectangle(
            self::INDENT_MULTISELECT,
            $this->lastPosition,
            self::INDENT_MULTISELECT + $rowLength,
            $this->lastPosition - self::ARRAY_LINE_HEIGHT,
            Zend_Pdf_Page::SHAPE_DRAW_STROKE
        );

        /**
         * @var int    $index
         * @var string $value
         */
        foreach ($values as $index => $value) {
            /** @var float $indentValueCell */
            $indentValueCell = ($cellLength * $index) + ($cellLength - $this->widthForStringUsingFontSize(
                        $value
                    )) / 2;

            $this->page->drawText(
                $value,
                self::INDENT_MULTISELECT + $indentValueCell,
                $this->lastPosition - 20
            );

            // Draw the right line of the cell
            $this->page->drawLine(
                self::INDENT_MULTISELECT + ($cellLength * $index) + $cellLength,
                $this->lastPosition,
                self::INDENT_MULTISELECT + ($cellLength * $index) + $cellLength,
                $this->lastPosition - self::ARRAY_LINE_HEIGHT
            );
        }

        $this->addLineBreak(0, self::ARRAY_LINE_HEIGHT);
    }
}
