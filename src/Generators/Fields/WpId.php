<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\Generators\Fields;

use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Number;

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
        $field = new Number;
        $field->name = 'WordPress ID';
        $field->handle = 'wpId';
        $field->min = 1;
        $field->searchable = true;
        $field->previewFormat = Number::FORMAT_NONE;

        return $field;
    }
}
