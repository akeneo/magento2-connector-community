<?php

declare(strict_types=1);

namespace Akeneo\Connector\Block\Widget;

use Magento\Backend\Block\Widget\Grid as BaseGrid;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Grid extends BaseGrid
{
    /**
     * {@override} Remove button filter "Search" & "Reset Filter" in job grid
     *
     * @return string
     */
    public function getMainButtonsHtml(): string
    {
        return '';
    }
}
