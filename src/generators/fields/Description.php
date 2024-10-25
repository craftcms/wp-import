<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\generators\fields;

use craft\base\FieldInterface;
use craft\fields\PlainText;
use craft\wpimport\BaseFieldGenerator;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Description extends BaseFieldGenerator
{
    protected static function uid(): string
    {
        return 'b636e420-0741-4bd3-b8b2-89a8d50806f9';
    }

    protected static function create(): FieldInterface
    {
        $field = new PlainText();
        $field->name = 'Description';
        $field->handle = 'description';
        $field->code = true;
        $field->multiline = true;
        $field->searchable = true;
        return $field;
    }
}
