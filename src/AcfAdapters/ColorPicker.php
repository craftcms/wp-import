<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\AcfAdapters;

use CraftCms\Cms\Field\Color;
use CraftCms\Cms\Field\Contracts\FieldInterface;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class ColorPicker extends BaseAcfAdapter
{
    public static function type(): string
    {
        return 'color_picker';
    }

    public function create(array $data): FieldInterface
    {
        $field = new Color;
        if ($data['default_value']) {
            $field->defaultColor = strtolower($data['default_value']);
        }

        return $field;
    }
}
