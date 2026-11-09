<?php

namespace CraftCms\WpImport\Generators\Fields;

use CraftCms\Ckeditor\Field;
use CraftCms\Cms\Field\Contracts\FieldInterface;

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
        $field = new Field;
        $field->name = 'Rich Caption';
        $field->handle = 'richCaption';
        $field->toolbar = ['bold', 'italic', 'link'];
        $field->searchable = true;

        return $field;
    }
}
