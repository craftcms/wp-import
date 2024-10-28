<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\acfadapters;

use craft\base\FieldInterface;
use craft\fields\Dropdown;
use craft\fields\MultiSelect;
use craft\wpimport\BaseAcfAdapter;
use DateTime;

/**
 * Base block transformer class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Select extends BaseAcfAdapter
{
    public static function type(): string
    {
        return 'select';
    }

    public function create(array $data): FieldInterface
    {
        $field = $data['multiple'] ? new MultiSelect() : new Dropdown();
        $field->options = [];
        foreach ($data['choices'] as $value => $label) {
            $field->options[] = [
                'label' => $label,
                'value' => $value,
                'default' => is_array($data['default_value'])
                    ? in_array($value, $data['default_value'])
                    : $value === $data['default_value']
            ];
        }
        return $field;
    }
}
