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
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class TextArea extends BaseAcfAdapter
{
    public static function type(): string
    {
        return 'textarea';
    }

    public function create(array $data): FieldInterface
    {
        $field = new PlainText();
        $field->placeholder = $data['placeholder'];
        $field->multiline = true;
        if ($data['rows']) {
            $field->initialRows = $data['rows'];
        }
        return $field;
    }
}
