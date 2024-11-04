<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\acfadapters;

use craft\base\FieldInterface;
use craft\fields\Time;
use craft\wpimport\BaseAcfAdapter;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class TimePicker extends BaseAcfAdapter
{
    public static function type(): string
    {
        return 'time_picker';
    }

    public function create(array $data): FieldInterface
    {
        return new Time();
    }
}
