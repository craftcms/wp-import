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
class Template extends BaseFieldGenerator
{
    protected static function uid(): string
    {
        return '7e2aabf9-48f2-4671-b232-546499a18d43';
    }

    protected static function create(): FieldInterface
    {
        $field = new PlainText();
        $field->name = 'Template';
        $field->handle = 'template';
        $field->code = true;
        return $field;
    }
}
