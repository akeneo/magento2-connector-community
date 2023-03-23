<?php

declare(strict_types=1);

namespace Akeneo\Connector\Model\Config;

use Akeneo\Connector\Block\Adminhtml\System\Config\Form\Field\Website;
use Akeneo\Connector\Helper\Config as ConfigHelper;
use Akeneo\Connector\Model\Backend\Json;
use Akeneo\Connector\Model\Source\Edition;
use Magento\Config\Model\Config\Backend\Serialized\ArraySerialized;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\App\Area;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Module\Dir;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\View\Asset\File;
use Magento\Framework\View\Asset\Repository;
use SimpleXMLElement;
use Zend_Pdf;
use Zend_Pdf_Canvas_Interface;
use Zend_Pdf_Exception;
use Zend_Pdf_Font;
use Zend_Pdf_Image;
use Zend_Pdf_Page;
use Zend_Pdf_Resource_Image;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
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
     * Description $jsonSerializer field
     *
     * @var JsonSerializer $jsonSerializer
     */
    protected $jsonSerializer;
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
    public const HIDDEN_FIELDS = [
        ConfigHelper::AKENEO_API_BASE_URL,
        ConfigHelper::AKENEO_API_PASSWORD,
        ConfigHelper::AKENEO_API_USERNAME,
        ConfigHelper::AKENEO_API_CLIENT_ID,
        ConfigHelper::AKENEO_API_CLIENT_SECRET,
    ];
    /**
     * Fields which not be included into the boolean management (Yes/No value)
     *
     * @var string[] BYPASS_BOOLEAN_FIELDS
     */
    public const BYPASS_BOOLEAN_FIELDS = [
        ConfigHelper::PRODUCT_TAX_CLASS,
        ConfigHelper::PRODUCTS_FILTERS_UPDATED_SINCE,
        ConfigHelper::PRODUCT_AKENEO_ATTRIBUTE_CODE_FOR_SKU,
        ConfigHelper::PRODUCT_WEBSITE_ATTRIBUTE,
        ConfigHelper::PRODUCT_ATTRIBUTE_MAPPING,
        ConfigHelper::PRODUCT_CONFIGURABLE_ATTRIBUTES,
        ConfigHelper::PRODUCTS_FILTERS_STATUS,
    ];
    /**
     * Description LINE_BREAK constant
     *
     * @var int LINE_BREAK
     */
    public const LINE_BREAK = 20;
    /**
     * Indentation for multiselect list
     *
     * @var int INDENT_MULTISELECT
     */
    public const INDENT_MULTISELECT = 120;
    /**
     * Indentation for footer
     *
     * @var int INDENT_FOOTER
     */
    public const INDENT_FOOTER = 50;
    /**
     * Indentation for attributes list
     *
     * @var int INDENT_TEXT
     */
    public const INDENT_TEXT = 100;
    /**
     * Indentation for group title
     *
     * @var int INDENT_GROUP
     */
    public const INDENT_GROUP = 80;
    /**
     * Indentation for table
     *
     * @var int INDENT_TABLE
     */
    public const INDENT_TABLE = self::INDENT_TEXT;
    /**
     * Default font size
     *
     * @param string DEFAULT_FONT_SIZE
     */
    public const DEFAULT_FONT_SIZE = 10;
    /**
     * Table font size
     *
     * @param string TABLE_FONT_SIZE
     */
    public const TABLE_FONT_SIZE = 10;
    /**
     * Array line Height
     *
     * @var int ARRAY_LINE_HEIGHT
     */
    public const ARRAY_LINE_HEIGHT = 30;
    /**
     * Bottom page border constant
     *
     * @var int BOTTOM_BORDER
     */
    public const BOTTOM_PAGE_BORDER = 20;
    /**
     * Description LOGO_PDF constant
     *
     * @var string LOGO_PDF
     */
    public const LOGO_PDF = 'Akeneo_Connector::images/logo.jpg';
    /**
     * Description PASSWORD_CHAR constant
     *
     * @var string PASSWORD_CHAR
     */
    public const PASSWORD_CHAR = '****';
    /**
     * Array key to get group in SystemConfigAttribute
     *
     * @var string ATTRIBUTE_GROUP_ARRAY_KEY
     */
    public const SYSTEM_ATTRIBUTE_GROUP_ARRAY_KEY = 'group';
    /**
     * Field type text
     *
     * @var string FIELD_TYPE_TEXT
     */
    public const FIELD_TYPE_TEXT = 'TEXT';
    /**
     * Array key to get value in SystemConfigAttribute
     *
     * @var string SYSTEM_ATTRIBUTE_VALUE_ARRAY_KEY
     */
    public const SYSTEM_ATTRIBUTE_VALUE_ARRAY_KEY = 'value';
    /**
     * Position on Y axis of the last element
     *
     * @var float $lastPosition
     */
    protected $lastPosition = 0;
    /**
     * Position on X axis of the last element (Only used for print label and value in different fonts)
     *
     * @var int $lastPositionX
     */
    protected $lastPositionX = 0;
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
     * Description $attributeModel field
     *
     * @var Attribute $attributeModel
     */
    protected $attributeModel;

    /**
     * ConfigManagement constructor
     *
     * @param ResourceConnection $resourceConnection
     * @param Edition            $sourceEdition
     * @param Reader             $moduleReader
     * @param ConfigHelper       $configHelper
     * @param Repository         $assetRepository
     * @param DirectoryList      $directoryList
     * @param JsonSerializer     $jsonSerializer
     * @param Website            $websiteFormField
     * @param Attribute          $attributeModel
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        Edition $sourceEdition,
        Reader $moduleReader,
        ConfigHelper $configHelper,
        Repository $assetRepository,
        DirectoryList $directoryList,
        JsonSerializer $jsonSerializer,
        Website $websiteFormField,
        Attribute $attributeModel
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->sourceEdition      = $sourceEdition;
        $this->moduleReader       = $moduleReader;
        $this->configHelper       = $configHelper;
        $this->assetRepository    = $assetRepository;
        $this->directoryList      = $directoryList;
        $this->jsonSerializer     = $jsonSerializer;
        $this->websiteFormField   = $websiteFormField;
        $this->assetRepository    = $assetRepository;
        $this->attributeModel     = $attributeModel;
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
        $group         = '';

        /**
         * @var int      $index
         * @var string[] $config
         */
        foreach ($configs as $index => $config) {
            /** @var string[] $labelAndGroup */
            $labelAndGroup = $this->getSystemConfigAttribute($config['path'], 'label');
            /** @var string $label */
            $label = (string)$labelAndGroup[self::SYSTEM_ATTRIBUTE_VALUE_ARRAY_KEY];
            if (!$label) {
                continue;
            }
            /** @var string $currentGroup */
            $currentGroup = (string)$labelAndGroup[self::SYSTEM_ATTRIBUTE_GROUP_ARRAY_KEY];
            if ($group !== $currentGroup) {
                $this->drawBoldText('- ' . $currentGroup, self::INDENT_GROUP, $this->lastPosition);
                $this->addLineBreak(self::LINE_BREAK);
                $group = $currentGroup;
            }

            $label .= ' : ';
            // Set bold font for the field label
            $this->drawBoldText($label, self::INDENT_TEXT, $this->lastPosition);

            // Manage serialized attribute
            /** @var string[] $backendModelAttribute */
            $backendModelAttribute = $this->getSystemConfigAttribute($config['path'], 'backend_model');
            /** @var string $backendModelAttributeValue */
            $backendModelAttributeValue = (string)$backendModelAttribute[self::SYSTEM_ATTRIBUTE_VALUE_ARRAY_KEY];
            if ($backendModelAttributeValue === ArraySerialized::class) {
                // Get array labels
                /** @var string[] $configValueUnserialized */
                $configValueUnserialized = $this->jsonSerializer->unserialize($config['value']);
                /** @var string[] $firstElement */
                $firstElement = reset($configValueUnserialized);

                if (!$firstElement) {
                    $value = $this->renderValue('', $config['path']);
                    $this->page->drawText(
                        $value,
                        $this->lastPositionX,
                        $this->lastPosition
                    );
                    $this->addLineBreak(self::LINE_BREAK);
                    continue;
                }
                /** @var string[] $firstElementKeys */
                $firstElementKeys = array_keys($firstElement);

                $this->insertSerializedArray(
                    $configValueUnserialized,
                    $firstElementKeys,
                    $config['path']
                );
                continue;
            }

            // Advanced filter field management
            if ($backendModelAttributeValue === Json::class) {
                /** @var string $text */
                $text = $config['value'];
                /** @var string $cleanValue */
                $cleanValue = preg_replace("/<br>|\n|\r|\r?|\s\s+/", "", $text ?? '');
                /** @var string[] $lines */
                $lines = str_split($cleanValue, 89);

                if (!$cleanValue) {
                    $value = $this->renderValue('', $config['path']);
                    $this->page->drawText(
                        $value,
                        $this->lastPositionX,
                        $this->lastPosition
                    );
                    $this->addLineBreak(self::LINE_BREAK);
                    continue;
                }
                $this->addLineBreak(self::LINE_BREAK);

                /** @var string $line */
                foreach ($lines as $line) {
                    $line = $this->renderValue($line, $config['path']);
                    $this->page->drawText($line, self::INDENT_TEXT, $this->lastPosition);
                    $this->addLineBreak(self::LINE_BREAK);
                }
                continue;
            }

            if ($config['value'] && strpos($config['value'], ',') !== false && !$backendModelAttributeValue) {
                $this->insertMultiselect($config['value'], $config['path']);
                continue;
            }

            if (in_array($config['path'], self::HIDDEN_FIELDS) && $config['value']) {
                $value = self::PASSWORD_CHAR;
            } else {
                $value = $config['value'];
            }

            if ($config['path'] === ConfigHelper::AKENEO_API_EDITION) {
                $value = $this->getEdition();
            }

            /** @var string $attribute */
            $attribute = $this->getSystemConfigAttribute($config['path'], 'source_model');
            /** @var string $type */
            $type = self::FIELD_TYPE_TEXT;

            if ($attribute) {
                $type = $attribute;
            }
            $value = $this->renderValue((string)$value, $config['path'], $type);
            $this->page->drawText($value, $this->lastPositionX, $this->lastPosition);

            if ($index === $configsNumber - 1) {
                $this->addLineBreak(50, self::LINE_BREAK);
                $this->addFooter();
                continue;
            }

            $this->addLineBreak(self::LINE_BREAK);
        }

        return $this->pdf;
    }

    /**
     * Description setPageStyle function
     *
     * @param string $font
     * @param float  $fontSize
     *
     * @return void
     * @throws Zend_Pdf_Exception
     */
    protected function setPageStyle(
        string $font = Zend_Pdf_Font::FONT_HELVETICA,
        float $fontSize = self::DEFAULT_FONT_SIZE
    ) {
        $style = new \Zend_Pdf_Style();
        $style->setLineColor(new \Zend_Pdf_Color_Rgb(0, 0, 0));
        $font = Zend_Pdf_Font::fontWithName($font);
        $style->setFont($font, $fontSize);
        $this->page->setStyle($style);
    }

    /**
     * Description drawBoldText function
     *
     * @param string $value
     * @param float  $x
     * @param float  $y
     *
     * @return void
     * @throws Zend_Pdf_Exception
     */
    protected function drawBoldText(string $value, float $x, float $y)
    {
        $this->setPageStyle(Zend_Pdf_Font::FONT_HELVETICA_BOLD);
        $this->page->drawText($value, $x, $y);
        $this->lastPositionX = self::INDENT_TEXT + $this->widthForStringUsingFontSize($value);
        $this->setPageStyle();
    }

    /**
     * Insert multiselect into the pdf
     *
     * @param string $values
     * @param string $field
     *
     * @return void
     * @throws Zend_Pdf_Exception
     */
    protected function insertMultiselect($values, string $field)
    {
        /** @var string[] $valuesArray */
        $valuesArray = explode(',', $values ?? '');
        /** @var string $value */
        foreach ($valuesArray as $value) {
            $this->addLineBreak(self::LINE_BREAK);
            $value = $this->renderValue($value, $field);
            $this->page->drawText('- ' . $value, self::INDENT_MULTISELECT, $this->lastPosition);
        }

        $this->addLineBreak();
    }

    /**
     * Description insertSerializedArray function
     *
     * @param string[] $values
     * @param string[] $headers
     * @param string   $field
     *
     * @return void
     * @throws Zend_Pdf_Exception
     */
    protected function insertSerializedArray(array $values, array $headers, string $field)
    {
        $this->setPageStyle(Zend_Pdf_Font::FONT_HELVETICA, self::TABLE_FONT_SIZE);
        // Load attributes code by attributes id
        $values = $this->loadAttributeCode($values, $field);
        /** @var float $maxLengthValue */
        $maxLengthValue = $this->getMaxLengthValue($values);

        /** @var float $rowLength */
        $rowLength = ($maxLengthValue * count($headers)) + 10;
        /** @var float $cellLength */
        $cellLength = $rowLength / count($headers);

        $this->addLineBreak(self::ARRAY_LINE_HEIGHT);
        $this->addArrayRow($headers, $cellLength, $rowLength, $field);
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
            $this->addArrayRow($arrayValues, $cellLength, $rowLength, $field);
        }

        $this->addLineBreak(self::ARRAY_LINE_HEIGHT);
        $this->setPageStyle();
    }

    /**
     * Description loadAttributeCode function
     *
     * @param mixed[] $values
     * @param string  $field
     *
     * @return mixed[]
     */
    protected function loadAttributeCode(array $values, string $field)
    {
        foreach ($values as $index => $value) {
            foreach ($value as $key => $attribute) {
                if ($field === ConfigHelper::PRODUCT_MEDIA_IMAGES && $key === 'attribute') {
                    /** @var Attribute $attributeObject */
                    $attributeEntity      = $this->attributeModel->load($attribute);
                    $values[$index][$key] = $attributeEntity->getAttributeCode();
                }
            }
        }

        return $values;
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
        $title = (string)__('Akeneo Connector for Adobe Commerce - Configuration export');
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

        $this->addLineBreak(0, $imageHeigth + self::LINE_BREAK);
    }

    /**
     * Description addNewPage function
     *
     * @param string $font
     *
     * @return void
     * @throws Zend_Pdf_Exception
     */
    protected function addNewPage(string $font = Zend_Pdf_Font::FONT_HELVETICA)
    {
        /** @var float $currentFontSize */
        $currentFontSize    = $this->page ? $this->page->getFontSize() : self::DEFAULT_FONT_SIZE;
        $this->page         = $this->pdf->newPage(Zend_Pdf_Page::SIZE_A4);
        $this->pdf->pages[] = $this->page;
        $this->setPageStyle($font, $currentFontSize);

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
     * @return string[]
     */
    protected function getSystemConfigAttribute(string $path, string $attributeName)
    {
        /** @var string[] $path */
        $path = explode('/', $path ?? '');
        /** @var string $etcDir */
        $etcDir = $this->moduleReader->getModuleDir(
            Dir::MODULE_ETC_DIR,
            'Akeneo_Connector'
        );
        /** @var mixed[] $xml */
        $xml = simplexml_load_file($etcDir . '/adminhtml/system.xml');
        /** @var string $label */
        $label = '';
        /** @var string $attributeGroup */
        $attributeGroup = '';

        /** @var SimpleXMLElement $group */
        foreach ($xml->{'system'}->{'section'}->{'group'} as $group) {
            /** @var string[] $attributes */
            $attributes = $group->attributes();
            if ((string)$attributes['id'] === $path[1]) {
                foreach ($group->{'field'} as $field) {
                    /** @var string[] $attributexs */
                    $attributes = $field->attributes();
                    if ((string)$attributes['id'] === $path[2]) {
                        $label          = $field->{$attributeName};
                        $attributeGroup = $group->{'label'};
                    }
                }
            }
        }

        return [
            'group' => $attributeGroup,
            'value' => $label,
        ];
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
        )->where('path like ?', '%akeneo_connector%')->order('path ASC');

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
        $this->setPageStyle(Zend_Pdf_Font::FONT_HELVETICA_OBLIQUE);
        /** @var string $text */
        $text = (string)__(
            "If you want to report a bug, ask a question or have a suggestion to make on Akeneo Connector for Adobe Commerce,"
        );
        /** @var string $text2 */
        $text2 = (string)__("please contact our Support Team.");

        $this->page->drawText($text, self::INDENT_FOOTER, $this->lastPosition - self::LINE_BREAK);
        $this->page->drawText($text2, self::INDENT_FOOTER, $this->lastPosition - (self::LINE_BREAK) * 2);
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
        if ($nextElementHeight === null) {
            $nextElementHeight = 0;
        }

        if ($this->lastPosition <= self::BOTTOM_PAGE_BORDER
            || ($this->lastPosition - $nextElementHeight <= self::BOTTOM_PAGE_BORDER)
        ) {
            $this->addNewPage();
        }

        if ($value === null) {
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
     * @param string   $field
     *
     * @return void
     * @throws Zend_Pdf_Exception
     */
    protected function addArrayRow(
        array $values,
        float $cellLength,
        float $rowLength,
        string $field
    ) {
        /** @var Zend_Pdf_Canvas_Interface $line */
        $this->page->drawRectangle(
            self::INDENT_TABLE,
            $this->lastPosition,
            self::INDENT_TABLE + $rowLength,
            $this->lastPosition - self::ARRAY_LINE_HEIGHT,
            Zend_Pdf_Page::SHAPE_DRAW_STROKE
        );

        /**
         * @var int    $index
         * @var string $value
         */
        foreach ($values as $index => $value) {
            $value = $this->renderValue($value, $field);

            /** @var float $indentValueCell */
            $indentValueCell = ($cellLength * $index) + ($cellLength - $this->widthForStringUsingFontSize($value)) / 2;

            $this->page->drawText($value, self::INDENT_TABLE + $indentValueCell, $this->lastPosition - 20);

            // Draw the right line of the cell
            $this->page->drawLine(
                self::INDENT_TABLE + ($cellLength * $index) + $cellLength,
                $this->lastPosition,
                self::INDENT_TABLE + ($cellLength * $index) + $cellLength,
                $this->lastPosition - self::ARRAY_LINE_HEIGHT
            );
        }

        $this->addLineBreak(self::ARRAY_LINE_HEIGHT * 2, self::ARRAY_LINE_HEIGHT);
    }

    /**
     * Description manageBooleanValue function
     *
     * @param string $value
     * @param bool   $bypassBoolean
     *
     * @return string
     */
    protected function manageBooleanValue(string $value, bool $bypassBoolean)
    {
        if (!$bypassBoolean && is_numeric($value) && preg_match("/^[0|1]$/", $value)) {
            if (preg_match("/^[1]$/", $value)) {
                return (string)__('Yes');
            } else {
                return (string)__('No');
            }
        }

        return $value;
    }

    /**
     * Description renderValue function
     *
     * @param string      $value
     * @param string      $field
     * @param null|string $fieldType
     *
     * @return string
     */
    protected function renderValue(string $value, string $field, $fieldType = null)
    {
        if (!$value && !is_numeric($value)) {
            return (string)__('Empty');
        }

        /** @var bool $bypass */
        $bypass = in_array($field, self::BYPASS_BOOLEAN_FIELDS) || $fieldType === self::FIELD_TYPE_TEXT;

        return $this->manageBooleanValue($value, $bypass);
    }
}
