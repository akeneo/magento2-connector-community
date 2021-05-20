<?php

declare(strict_types=1);

namespace Akeneo\Connector\Executor;

use Akeneo\Connector\Api\JobExecutorInterface;
use Magento\Framework\Phrase;

/**
 * Class JobExecutor
 *
 * @package   Akeneo\Connector\Executor
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class JobExecutor implements JobExecutorInterface
{
    /**
     * Description execute function
     *
     * @param string $code
     *
     * @return \Magento\Framework\Phrase|null
     */
    public function execute(string $code): ?Phrase
    {
        // @TODO Implement execute() method.
    }
}
