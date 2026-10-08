<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\acfadapters;

use craft\base\FieldInterface;
use craft\ckeditor\Field;
use craft\helpers\ArrayHelper;
use craft\helpers\StringHelper;
use craft\wpimport\BaseAcfAdapter;

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
        $field = new Field();
        $toolbar = $this->toolbar($data['toolbar']);
        if ($toolbar) {
            $field->toolbar = $toolbar;
        }
        return $field;
    }

    private function toolbar(string $name): ?array
    {
        $toolbarName = ArrayHelper::firstWhere(
            array_keys($this->command->wpInfo['wysiwyg_toolbars']),
            fn(string $key) => StringHelper::toSnakeCase($key) === $name,
        );

        if (!$toolbarName) {
            return null;
        }

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

            if (!empty($rowButtons)) {
                if (!empty($toolbar)) {
                    $toolbar[] = '|';
                }
                array_push($toolbar, ...array_values(array_unique($rowButtons)));
            }
        }

        return $toolbar ?: null;
    }
}
