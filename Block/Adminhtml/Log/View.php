<?php

declare(strict_types=1);

namespace Akeneo\Connector\Block\Adminhtml\Log;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Model\UrlFactory;
use Magento\Backend\Model\UrlInterface;
use Akeneo\Connector\Api\Data\LogInterface;
use Akeneo\Connector\Api\LogRepositoryInterface;
use Magento\Framework\App\RequestInterface;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class View extends Template
{
    protected UrlInterface $urlModel;

    protected LogRepositoryInterface $logRepository;

    protected RequestInterface $request;

    /**
     * View constructor
     *
     * @param LogRepositoryInterface $logRepository
     * @param UrlFactory $backendUrlFactory
     * @param RequestInterface $request
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        LogRepositoryInterface $logRepository,
        UrlFactory $backendUrlFactory,
        RequestInterface $request,
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->urlModel = $backendUrlFactory->create();
        $this->logRepository = $logRepository;
        $this->request = $request;
    }

    /**
     * Retrieve log
     *
     * @return LogInterface
     */
    public function getLog()
    {
        return $this->logRepository->get($this->getLogId());
    }

    /**
     * Retrieve steps
     *
     * @return array
     */
    public function getSteps()
    {
        $steps = [
            ['status' => 'error', 'number' => 0, 'message' => __('No Log')]
        ];
        $log = $this->getLog();

        if ($log->hasData()) {
            $steps = $this->logRepository->getSteps((int)$log->getId());
        }

        return $steps;
    }

    /**
     * Retrieve log id
     *
     * @return int
     */
    public function getLogId()
    {
        return (int)$this->request->getParam('log_id');
    }

    /**
     * Retrieve back URL
     *
     * @return string
     */
    public function getBackUrl()
    {
        return $this->urlModel->getUrl('akeneo_connector/log');
    }
}
