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
use craft\validators\ColorValidator;
use craft\wpimport\Command;
use craft\wpimport\errors\InvalidColor;

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
        $validator = new ColorValidator();
        foreach ($command->wpInfo['color_palette'] as $palette) {
            foreach ($palette as $paletteColor) {
                $normalized = ColorValidator::normalizeColor($paletteColor['color']);
                if ($validator->validate($normalized)) {
                    $presets[strtolower($normalized)] = true;
                } else {
                    $command->errors[] = new InvalidColor($paletteColor['color']);
                }
            }
        }
        $field->presets = array_keys($presets);

        return $field;
    }
}
