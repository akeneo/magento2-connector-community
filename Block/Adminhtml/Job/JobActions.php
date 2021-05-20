<?php

declare(strict_types=1);

namespace Akeneo\Connector\Block\Adminhtml\Job;

use Akeneo\Connector\Api\Data\JobInterface;
use Magento\Backend\Block\Context;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use Magento\Framework\UrlInterface;

/**
 * Class JobActions
 *
 * @package   Akeneo\Connector\Block\Adminhtml\Job
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class JobActions extends AbstractRenderer
{
    /**
     * Description $urlBuilder field
     *
     * @var UrlInterface $urlBuilder
     */
    protected $urlBuilder;

    /**
     * JobActions constructor
     *
     * @param Context      $context
     * @param UrlInterface $urlBuilder
     * @param string[]     $data
     */
    public function __construct(
        Context $context,
        UrlInterface $urlBuilder,
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;

        parent::__construct($context, $data);
    }

    /**
     * Render action
     *
     * @param DataObject $row
     *
     * @return string
     */
    public function render(DataObject $row)
    {
        /** @var string $filterEncoded */
        $filterEncoded = base64_encode(JobInterface::CODE . '=' . $row->getCode());
        /** @var string $href */
        $href = $this->urlBuilder->getUrl(
            'akeneo_connector/log/index',
            ['filter' => $filterEncoded]
        );

        return '<a href="' . $href . '">' . __('View Logs') . '</a>';
    }
}
