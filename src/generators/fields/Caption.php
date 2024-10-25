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
class Caption extends BaseFieldGenerator
{
    protected static function uid(): string
    {
        return '4c8edf71-ef0c-4ba3-ba4e-cf0ef5ac36ea';
    }

    protected static function create(): FieldInterface
    {
        $field = new PlainText();
        $field->name = 'Caption';
        $field->handle = 'caption';
        $field->multiline = true;
        $field->searchable = true;
        return $field;
    }
}
