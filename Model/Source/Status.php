<?php

declare(strict_types=1);

namespace Akeneo\Connector\Model\Source;

use Akeneo\Connector\Api\Data\JobInterface;
use Magento\Framework\Option\ArrayInterface;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Status implements ArrayInterface
{
    /**
     * Return array of options for the status filter
     *
     * @return array Format: array('<value>' => '<label>', ...)
     */
    public function toOptionArray()
    {
        return [
            [
                'label' => __(' '),
                'value' => '',
            ],
            [
                'label' => __('Success'),
                'value' => JobInterface::JOB_SUCCESS,
            ],
            [
                'label' => __('Error'),
                'value' => JobInterface::JOB_ERROR,
            ],
            [
                'label' => __('Processing'),
                'value' => JobInterface::JOB_PROCESSING,
            ],
            [
                'label' => __('Pending'),
                'value' => JobInterface::JOB_PENDING,
            ],
            [
                'label' => __('Scheduled'),
                'value' => JobInterface::JOB_SCHEDULED,
            ],
        ];
    }
}
