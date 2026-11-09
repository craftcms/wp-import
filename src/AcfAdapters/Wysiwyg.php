<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\AcfAdapters;

use CraftCms\Ckeditor\Field;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Str;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Wysiwyg extends BaseAcfAdapter
{
    public static function type(): string
    {
        return 'wysiwyg';
    }

    public function create(array $data): FieldInterface
    {
        $field = new Field;
        $toolbarName = Arr::first(
            array_keys($this->command->wpInfo['wysiwyg_toolbars']),
            fn (string $key) => Str::snake($key) === $data['toolbar'],
        );
        if ($toolbarName) {
            $field->toolbar = $this->toolbar($toolbarName);
        }

        return $field;
    }

    private function toolbar(string $toolbarName): array
    {
        $toolbar = [];

        foreach ($this->command->wpInfo['wysiwyg_toolbars'][$toolbarName] as $row) {
            $rowButtons = [];
            foreach ($row as $button) {
                $ckeButton = match ($button) {
                    'formatselect' => 'heading',
                    'bold' => 'bold',
                    'italic' => 'italic',
                    'bullist' => 'bulletedList',
                    'numlist' => 'numberedList',
                    'blockquote' => 'blockQuote',
                    'alignleft', 'aligncenter', 'alignright' => 'alignment',
                    'link' => 'link',
                    'wp_more' => 'pageBreak',
                    'strikethrough' => 'strikethrough',
                    'hr' => 'horizontalLine',
                    'forecolor' => 'fontColor',
                    'removeformat' => 'removeFormat',
                    'outdent' => 'outdent',
                    'indent' => 'indent',
                    'undo' => 'undo',
                    'redo' => 'redo',
                    default => null,
                };
                if ($ckeButton) {
                    $rowButtons[] = $ckeButton;
                }
            }

            if (! empty($rowButtons)) {
                if (! empty($toolbar)) {
                    $toolbar[] = '|';
                }
                array_push($toolbar, ...array_values(array_unique($rowButtons)));
            }
        }

        return $toolbar;
    }
}
