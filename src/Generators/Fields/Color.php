<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\Generators\Fields;

use CraftCms\Cms\Field\Color as ColorField;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\WpImport\Console\ImportCommand;
use CraftCms\WpImport\Exceptions\InvalidColor;

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
        $field = new ColorField;
        $field->name = 'Color';
        $field->handle = 'color';

        /** @var ImportCommand $command */
        $command = app(ImportCommand::class);
        $presets = [];
        foreach ($command->wpInfo['color_palette'] as $palette) {
            foreach ($palette as $paletteColor) {
                $normalized = self::normalizeColor($paletteColor['color']);
                if (preg_match('/^#[0-9a-f]{6}$/', $normalized)) {
                    $presets[strtolower($normalized)] = true;
                } else {
                    $command->errors[] = new InvalidColor($paletteColor['color']);
                }
            }
        }
        $field->setPresets(array_keys($presets));

        return $field;
    }

    /**
     * Normalizes a hex color (`fff` → `#ffffff`).
     */
    private static function normalizeColor(string $color): string
    {
        $color = strtolower(ltrim(trim($color), '#'));
        if (strlen($color) === 3) {
            $color = $color[0].$color[0].$color[1].$color[1].$color[2].$color[2];
        }

        return "#$color";
    }
}
