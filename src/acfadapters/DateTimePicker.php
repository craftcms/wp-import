<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\acfadapters;

use craft\base\FieldInterface;
use craft\fields\Date;
use craft\helpers\DateTimeHelper;
use craft\wpimport\BaseAcfAdapter;

/**
 * Base block transformer class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class DateTimePicker extends BaseAcfAdapter
{
    public static function type(): string
    {
        return 'date_time_picker';
    }

    public function create(array $data): FieldInterface
    {
        $field = new Date();
        $field->showTime = true;
        return $field;
    }

    public function normalizeValue(mixed $value, array $data): mixed
    {
        return DateTimeHelper::toDateTime($value);
    }
}
