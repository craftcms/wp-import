<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\acfadapters;

use craft\base\FieldInterface;
use craft\fields\Date;
use craft\wpimport\BaseAcfAdapter;
use DateTime;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class DatePicker extends BaseAcfAdapter
{
    public static function type(): string
    {
        return 'date_picker';
    }

    public function create(array $data): FieldInterface
    {
        return new Date();
    }

    public function normalizeValue(mixed $value, array $data): mixed
    {
        return DateTime::createFromFormat('Ymd', $value);
    }
}
