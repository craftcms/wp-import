<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\Generators\Fields;

use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Dropdown;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Format extends BaseFieldGenerator
{
    protected static function uid(): string
    {
        return 'd958a069-fc64-431a-91bc-3141612304be';
    }

    protected static function create(): FieldInterface
    {
        $field = new Dropdown;
        $field->name = 'Format';
        $field->handle = 'format';
        $field->options = [
            ['label' => 'Standard', 'value' => 'standard', 'default' => true],
            ['label' => 'Aside', 'value' => 'aside'],
            ['label' => 'Audio', 'value' => 'audio'],
            ['label' => 'Chat', 'value' => 'chat'],
            ['label' => 'Gallery', 'value' => 'gallery'],
            ['label' => 'Image', 'value' => 'image'],
            ['label' => 'Link', 'value' => 'link'],
            ['label' => 'Quote', 'value' => 'quote'],
            ['label' => 'Status', 'value' => 'status'],
            ['label' => 'Video', 'value' => 'video'],
        ];

        return $field;
    }
}
