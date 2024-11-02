<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\generators\fields;

use craft\base\FieldInterface;
use verbb\comments\fields\CommentsField;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Comments extends BaseFieldGenerator
{
    protected static function uid(): string
    {
        return 'c23bcc94-9f9b-42ae-aca6-b5afb8be5e86';
    }

    protected static function create(): FieldInterface
    {
        $field = new CommentsField();
        $field->name = 'Comment Options';
        $field->handle = 'commentOptions';
        return $field;
    }
}
