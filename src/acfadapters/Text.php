<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\acfadapters;

use craft\base\FieldInterface;
use craft\fields\PlainText;
use craft\wpimport\BaseAcfAdapter;

/**
 * Base block transformer class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Text extends BaseAcfAdapter
{
    public static function type(): string
    {
        return 'text';
    }

    public function create(array $data): FieldInterface
    {
        $field = new PlainText();
        $field->placeholder = $data['placeholder'];
        return $field;
    }
}
