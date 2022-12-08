<?php

declare(strict_types=1);

namespace Akeneo\Connector\Api;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
interface JobExecutorInterface
{
    /**
     * Description execute function
     *
     * @param string $code
     * @param OutputInterface|null $output
     *
     * @return bool
     */
    public function execute(string $code, ?OutputInterface $output);
}
