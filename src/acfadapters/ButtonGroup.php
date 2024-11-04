<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\acfadapters;

use craft\base\FieldInterface;
use craft\fields\Dropdown;
use craft\wpimport\BaseAcfAdapter;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class ButtonGroup extends BaseAcfAdapter
{
    public static function type(): string
    {
        return 'button_group';
    }

    public function create(array $data): FieldInterface
    {
        $field = new Dropdown();
        $field->options = [];
        foreach ($data['choices'] as $value => $label) {
            $field->options[] = [
                'label' => $label,
                'value' => $value,
                'default' => is_array($data['default_value'])
                    ? in_array($value, $data['default_value'])
                    : $value === $data['default_value'],
            ];
        }
        return $field;
    }
}
