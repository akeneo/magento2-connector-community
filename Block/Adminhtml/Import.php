<?php

namespace Akeneo\Connector\Block\Adminhtml;

use \Magento\Backend\Block\Template;
use Magento\Backend\Model\Url;
use \Magento\Backend\Model\UrlFactory;
use \Magento\Backend\Block\Template\Context;
use Akeneo\Connector\Api\ImportRepositoryInterface;

/**
 * Class Import
 *
 * @category  Class
 * @package   Akeneo\Connector\Block\Adminhtml
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Import extends Template
{

    /**
     * This variable contains an Url
     *
     * @var Url $urlModel
     */
    protected $urlModel;
    /**
     * This variable contains an ImportRepositoryInterface
     *
     * @var ImportRepositoryInterface $importRepository
     */
    private $importRepository;

    /**
     * Import constructor.
     *
     * @param Context $context
     * @param UrlFactory $backendUrlFactory
     * @param ImportRepositoryInterface $importRepository
     * @param array $data
     */
    public function __construct(
        Context $context,
        UrlFactory $backendUrlFactory,
        ImportRepositoryInterface $importRepository,
        $data = []
    ) {
        parent::__construct($context, $data);

        $this->urlModel         = $backendUrlFactory->create();
        $this->importRepository = $importRepository;
    }

    /**
     * Retrieve import collection
     *
     * @return Iterable
     * @throws \Exception
     */
    public function getCollection()
    {
        return $this->importRepository->getList();
    }

    /**
     * Check import is allowed
     *
     * @param string $code
     *
     * @return bool
     */
    public function isAllowed($code)
    {
        return $this->_authorization->isAllowed('Akeneo_Connector::import_'.$code);
    }

    /**
     * {@inheritdoc}
     */
    public function _toHtml()
    {
        /** @var string $runUrl */
        $runUrl = $this->_getRunUrl();

        $this->assign(
            [
                'runUrl' => $this->_escaper->escapeHtml($runUrl),
            ]
        );

        return parent::_toHtml();
    }

    /**
     * Retrieve run URL
     *
     * @return string
     */
    public function _getRunUrl()
    {
        return $this->urlModel->getUrl('akeneo_connector/import/run');
    }
}
