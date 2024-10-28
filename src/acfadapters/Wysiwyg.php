<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\acfadapters;

use craft\base\FieldInterface;
use craft\ckeditor\CkeConfig;
use craft\ckeditor\Field;
use craft\ckeditor\Plugin;
use craft\helpers\ArrayHelper;
use craft\helpers\StringHelper;
use craft\wpimport\BaseAcfAdapter;

/**
 * Base block transformer class
 *
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
        $field->ckeConfig = $this->ckeConfig($data['toolbar'])?->uid;
        return $field;
    }

    private function ckeConfig(string $name): ?CkeConfig
    {
        $configsService = Plugin::getInstance()->getCkeConfigs();
        /** @var CkeConfig|null $config */
        $config = ArrayHelper::firstWhere(
            $configsService->getAll(),
            fn(CkeConfig $config) => StringHelper::toSnakeCase($config->name) === $name,
        );
        if ($config) {
            return $config;
        }

        $toolbarName = ArrayHelper::firstWhere(
            array_keys($this->command->wpInfo['wysiwyg_toolbars']),
            fn(string $key) => StringHelper::toSnakeCase($key) === $name,
        );

        if (!$toolbarName) {
            return null;
        }

        $config = new CkeConfig();
        $config->uid = StringHelper::UUID();
        $config->name = $toolbarName;
        $config->toolbar = [];

        foreach ($this->command->wpInfo['wysiwyg_toolbars'][$toolbarName] as $row) {
            $rowButtons = [];
            foreach ($row as $button) {
                $ckeButton = match($button) {
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
                if (!empty($config->toolbar)) {
                    $config->toolbar[] = '|';
                }
                array_push($config->toolbar, ...array_values(array_unique($rowButtons)));
            }
        }

        $this->command->do("Creating `$config->name` CKEditor config", function() use ($configsService, $config) {
            $configsService->save($config);
        });

        return $config;
    }
}
