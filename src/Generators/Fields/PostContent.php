<?php

namespace CraftCms\WpImport\Generators\Fields;

use CraftCms\Ckeditor\Field;
use CraftCms\Cms\Field\Contracts\FieldInterface;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class PostContent extends BaseFieldGenerator
{
    protected static function uid(): string
    {
        return 'e314b2cd-4ad3-4d8c-94d8-347ddb16245d';
    }

    protected static function create(): FieldInterface
    {
        $field = new Field;
        $field->name = 'Post Content';
        $field->handle = 'postContent';
        $field->toolbar = [
            'heading', '|',
            'bold', 'italic', 'link', '|',
            'blockQuote', 'bulletedList', 'numberedList', 'codeBlock', '|',
            'insertTable', 'mediaEmbed', 'htmlEmbed', 'horizontalLine', 'pageBreak', '|',
            'createEntry', 'sourceEditing',
        ];
        $field->sanitizeHtml = false; // todo: allow video embeds in the sanitizer config
        $field->searchable = true;

        return $field;
    }
}
