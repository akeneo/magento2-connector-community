<?php

declare(strict_types=1);

namespace Akeneo\Connector\Api\Data;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
interface ImportInterface
{
    /**
     * @var int IMPORT_SUCCESS
     */
    public const IMPORT_SUCCESS = 1;
    /**
     * @var int IMPORT_ERROR
     */
    public const IMPORT_ERROR = 2;
    /**
     * @var int IMPORT_PROCESSING
     */
    public const IMPORT_PROCESSING = 3;
}
