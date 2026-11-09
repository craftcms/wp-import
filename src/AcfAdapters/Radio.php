<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\AcfAdapters;

use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\RadioButtons;

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
        $field = new RadioButtons;
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
        $field->customOptions = (bool) $data['other_choice'];

        return $field;
    }
}
