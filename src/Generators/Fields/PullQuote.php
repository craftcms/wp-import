<?php

namespace CraftCms\WpImport\Generators\Fields;

use CraftCms\Ckeditor\Field;
use CraftCms\Cms\Field\Contracts\FieldInterface;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class PullQuote extends BaseFieldGenerator
{
    protected static function uid(): string
    {
        return 'cdbd7117-1123-4e4b-b255-dd1c92d3c864';
    }

    protected static function create(): FieldInterface
    {
        $field = new Field;
        $field->name = 'Pull Quote';
        $field->handle = 'pullQuote';
        $field->toolbar = [
            'heading', 'bold', 'italic', 'link', '|',
            'code', 'strikethrough', 'subscript', 'superscript',
        ];
        $field->searchable = true;

        return $field;
    }
}
