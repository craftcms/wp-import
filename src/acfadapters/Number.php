<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\acfadapters;

use craft\base\FieldInterface;
use craft\fields\Number as NumberField;
use craft\wpimport\BaseAcfAdapter;

/**
 * Base block transformer class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Number extends BaseAcfAdapter
{
    public static function type(): string
    {
        return 'number';
    }

    public function create(array $data): FieldInterface
    {
        $field = new NumberField();
        if ($data['min']) {
            $field->min = $data['min'];
        }
        if ($data['max']) {
            $field->max = $data['max'];
        }
        if ($data['step']) {
            $field->step = $data['step'];
        }
        if ($data['prepend']) {
            $field->prefix = $data['prepend'];
        }
        if ($data['append']) {
            $field->suffix = $data['append'];
        }
        return $field;
    }
}
