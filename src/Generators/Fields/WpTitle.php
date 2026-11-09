<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\generators\fields;

use craft\base\FieldInterface;
use craft\fields\PlainText;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class WpTitle extends BaseFieldGenerator
{
    protected static function uid(): string
    {
        return '142f6f9a-7cda-47a3-887b-0b31ad15f101';
    }

    protected static function create(): FieldInterface
    {
        $field = new PlainText();
        $field->name = 'WordPress Title';
        $field->handle = 'wpTitle';
        return $field;
    }
}
