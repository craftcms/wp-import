<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\generators\fields;

use Craft;
use craft\base\FieldInterface;
use craft\fields\Color as ColorField;
use craft\wpimport\BaseFieldGenerator;
use craft\wpimport\Command;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Color extends BaseFieldGenerator
{
    protected static function uid(): string
    {
        return 'ec2af108-def7-419e-b9fd-53f0d4289b0d';
    }

    protected static function create(): FieldInterface
    {
        $field = new ColorField();
        $field->name = 'Color';
        $field->handle = 'color';

        /** @var Command $command */
        $command = Craft::$app->controller;
        $presets = [];
        foreach ($command->wpSettings['color_palette'] as $palette) {
            foreach ($palette as $paletteColor) {
                $presets[$paletteColor['color']] = true;
            }
        }
        $field->presets = array_keys($presets);

        return $field;
    }
}
