<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\generators\fields;

use craft\base\FieldInterface;
use craft\fields\Number;
use craft\wpimport\BaseFieldGenerator;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class WpId extends BaseFieldGenerator
{
    protected static function uid(): string
    {
        return '4d06012a-2d8a-4e92-bf6b-de74bdd1be0b';
    }

    protected static function create(): FieldInterface
    {
        $field = new Number();
        $field->name = 'WordPres ID';
        $field->handle = 'wpId';
        $field->min = 1;
        $field->searchable = true;
        $field->previewFormat = Number::FORMAT_NONE;
        return $field;
    }
}
