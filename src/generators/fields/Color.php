<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\generators\fields;

use craft\base\FieldInterface;
use craft\fields\Color as ColorField;
use craft\wpimport\BaseFieldGenerator;

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
        return $field;
    }
}