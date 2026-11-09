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
class Summary extends BaseFieldGenerator
{
    protected static function uid(): string
    {
        return 'dee725db-134a-40f3-bd54-199353365c57';
    }

    protected static function create(): FieldInterface
    {
        $field = new PlainText;
        $field->name = 'Summary';
        $field->handle = 'summary';
        $field->searchable = true;

        return $field;
    }
}
