<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\generators\fields;

use craft\base\FieldInterface;
use craft\ckeditor\Field;
use craft\wpimport\generators\ckeconfigs\Simple;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class RichCaption extends BaseFieldGenerator
{
    protected static function uid(): string
    {
        return '22328587-6c0d-499f-bb07-eb7bd7e68bbf';
    }

    protected static function create(): FieldInterface
    {
        $field = new Field();
        $field->name = 'Rich Caption';
        $field->handle = 'richCaption';
        $field->ckeConfig = Simple::get()->uid;
        $field->searchable = true;
        return $field;
    }
}
