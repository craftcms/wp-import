<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\acfadapters;

use craft\base\FieldInterface;
use craft\fields\Range as RangeField;
use craft\wpimport\BaseAcfAdapter;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Range extends BaseAcfAdapter
{
    public static function type(): string
    {
        return 'range';
    }

    public function create(array $data): FieldInterface
    {
        $field = new RangeField();
        $field->min = $data['min'] ?: 0;
        $field->max = $data['max'] ?: 100;
        $field->step = $data['step'] ?: 1;
        if ($data['append']) {
            $field->suffix = $data['append'];
        }
        return $field;
    }
}
