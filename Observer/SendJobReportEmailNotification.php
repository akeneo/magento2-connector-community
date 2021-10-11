<?php

declare(strict_types=1);

namespace Akeneo\Connector\Observer;

use Akeneo\Connector\Api\Data\JobInterface;
use Akeneo\Connector\Api\Data\LogInterface;
use Akeneo\Connector\Api\JobExecutorInterface;
use Akeneo\Connector\Helper\Config;
use Akeneo\Connector\Model\LogRepository;
use Magento\Framework\App\Area;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Mail\Template\SenderResolverInterface;
use Magento\Framework\Mail\TransportInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class SendEmailNotification
 *
 * @package   Dnd\Document\Observer
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class SendJobReportEmailNotification implements ObserverInterface
{
    /**
     * Job report notification email template
     *
     * @var string JOB_REPORT_NOTIFICATION_EMAIL_TEMPLATE
     */
    const JOB_REPORT_NOTIFICATION_EMAIL_TEMPLATE = 'akeneo_connector_report_email';
    /**
     * Description $config field
     *
     * @var Config $config
     */
    protected $config;
    /**
     * Description $senderResolver field
     *
     * @var SenderResolverInterface $senderResolver
     */
    protected $senderResolver;
    /**
     * Description $transportBuilder field
     *
     * @var TransportBuilder $transportBuilder
     */
    protected $transportBuilder;
    /**
     * Description $storeManagerInterface field
     *
     * @var StoreManagerInterface $storeManagerInterface
     */
    protected $storeManagerInterface;
    /**
     * Description $logRepository field
     *
     * @var LogRepository $logRepository
     */
    protected $logRepository;
    /**
     * Description $url field
     *
     * @var UrlInterface $url
     */
    protected $url;

    /**
     * SendEmailNotification constructor
     *
     * @param Config                  $config
     * @param SenderResolverInterface $senderResolver
     * @param TransportBuilder        $transportBuilder
     * @param StoreManagerInterface   $storeManager
     * @param LogRepository           $logRepository
     * @param UrlInterface            $url
     */
    public function __construct(
        Config $config,
        SenderResolverInterface $senderResolver,
        TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManager,
        LogRepository $logRepository,
        UrlInterface $url
    ) {
        $this->config                = $config;
        $this->senderResolver        = $senderResolver;
        $this->transportBuilder      = $transportBuilder;
        $this->storeManagerInterface = $storeManager;
        $this->logRepository         = $logRepository;
        $this->url                   = $url;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer): void
    {
        /** @var JobExecutorInterface $executor */
        $executor = $observer->getEvent()->getExecutor();
        /** @var LogInterface $log */
        $log = $this->logRepository->getByIdentifier($executor->getIdentifier());

        $recipients = $this->config->getJobReportRecipient();
        if ($recipients) {
            $this->sendEmail($recipients, $log, $executor);
        }
    }

    /**
     * Description sendEmail function
     *
     * @param string[]             $recipients
     * @param LogInterface         $log
     * @param JobExecutorInterface $executor
     *
     * @return void
     * @throws LocalizedException
     * @throws MailException
     * @throws NoSuchEntityException
     */
    private function sendEmail(array $recipients, LogInterface $log, JobExecutorInterface $executor): void
    {
        /** @var string $jobStatus */
        $jobStatus = $executor->getCurrentJobClass()->getStatusLabel();
        /** @var int $logId */
        $logId = $log->getId();
        /** @var string $link */
        $link = $this->url->getUrl('akeneo_connector/log/view', ['log_id' => $logId]);
        /** @var TransportBuilder $transportBuilder */
        $transportBuilder = $this->transportBuilder->setTemplateIdentifier(self::JOB_REPORT_NOTIFICATION_EMAIL_TEMPLATE)
            ->setTemplateOptions(
                [
                    'area'  => Area::AREA_FRONTEND,
                    'store' => $this->storeManagerInterface->getStore()->getId(),
                ]
            )
            ->setTemplateVars(
                [
                    'link'      => $link,
                    'jobStatus' => $jobStatus,
                ]
            )
            ->setFromByScope(['email' => 'bastien.witczak@gmail.com', 'name' => '']);

        /** @var string $recipient */
        foreach ($recipients as $recipient) {
            $transportBuilder->addTo($recipient);
        }

        /** @var TransportInterface $transport */
        $transport = $transportBuilder->getTransport();

        $transport->sendMessage();
    }
}
