<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\generators\fields;

use craft\base\FieldInterface;
use craft\fields\Lightswitch;
use craft\wpimport\BaseFieldGenerator;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Sticky extends BaseFieldGenerator
{
    protected static function uid(): string
    {
        return '79559275-ab81-4420-abf1-1c848a0a68d6';
    }

    protected static function create(): FieldInterface
    {
        $field = new Lightswitch();
        $field->name = 'Sticky';
        $field->handle = 'sticky';
        return $field;
    }
}
