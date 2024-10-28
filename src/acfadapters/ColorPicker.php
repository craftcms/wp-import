<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\acfadapters;

use craft\base\FieldInterface;
use craft\fields\Color;
use craft\wpimport\BaseAcfAdapter;

/**
 * Base block transformer class
 *
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
        $field = new Color();
        if ($data['default_value']) {
            $field->defaultColor = $data['default_value'];
        }
        return $field;
    }
}
