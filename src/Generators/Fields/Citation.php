<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\Generators\Fields;

use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\PlainText;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Citation extends BaseFieldGenerator
{
    protected static function uid(): string
    {
        return '2de0e315-34d2-41cf-aca9-1d519c225296';
    }

    protected static function create(): FieldInterface
    {
        $field = new PlainText;
        $field->name = 'Citation';
        $field->handle = 'citation';
        $field->searchable = true;

        return $field;
    }
}
