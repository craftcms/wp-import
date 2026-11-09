<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\AcfAdapters;

use CraftCms\Cms\Field\Checkboxes;
use CraftCms\Cms\Field\Contracts\FieldInterface;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Checkbox extends BaseAcfAdapter
{
    public static function type(): string
    {
        return 'checkbox';
    }

    public function create(array $data): FieldInterface
    {
        $field = new Checkboxes;
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
        $field->customOptions = (bool) $data['allow_custom'];

        return $field;
    }
}
