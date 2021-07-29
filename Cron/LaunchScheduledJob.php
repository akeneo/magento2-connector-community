<?php

declare(strict_types=1);

namespace Akeneo\Connector\Cron;

use Akeneo\Connector\Executor\JobExecutor;
use Magento\Cron\Model\Schedule;

/**
 * Class LaunchScheduledJob
 *
 * @package   Akeneo\Connecot
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class LaunchScheduledJob
{
    /**
     * Description $jobExecutor field
     *
     * @var JobExecutor $jobExecutor
     */
    protected $jobExecutor;

    /**
     * LaunchScheduledJob constructor
     *
     * @param JobExecutor $jobExecutor
     */
    public function __construct(JobExecutor $jobExecutor)
    {
        $this->jobExecutor = $jobExecutor;
    }

    /**
     * Description execute function
     *
     * @param Schedule $schedule
     *
     * @return void
     */
    public function execute(Schedule $schedule)
    {

    }
}
