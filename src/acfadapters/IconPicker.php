<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\acfadapters;

use craft\base\FieldInterface;
use craft\fields\Icon as IconField;
use craft\wpimport\BaseAcfAdapter;

/**
 * Base block transformer class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class IconPicker extends BaseAcfAdapter
{
    public static function type(): string
    {
        return 'icon_picker';
    }

    public function create(array $data): FieldInterface
    {
        return new IconField();
    }

    public function normalizeValue(mixed $value, array $data): mixed
    {
        if ($value['type'] === 'dashicons') {
            return $this->command->normalizeIcon($value['value']);
        }
        return null;
    }
}
