<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\acfadapters;

use craft\base\FieldInterface;
use craft\fields\RadioButtons;
use craft\wpimport\BaseAcfAdapter;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Radio extends BaseAcfAdapter
{
    public static function type(): string
    {
        return 'radio';
    }

    public function create(array $data): FieldInterface
    {
        $field = new RadioButtons();
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
        $field->customOptions = (bool)$data['other_choice'];
        return $field;
    }
}
