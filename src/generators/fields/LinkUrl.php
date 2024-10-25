<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\generators\fields;

use craft\base\FieldInterface;
use craft\fields\Link;
use craft\fields\linktypes\Url;
use craft\wpimport\BaseFieldGenerator;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class LinkUrl extends BaseFieldGenerator
{
    protected static function uid(): string
    {
        return '22e94a09-0f7c-4a1f-9981-7fc0605d83fa';
    }

    protected static function create(): FieldInterface
    {
        $field = new Link();
        $field->name = 'Link URL';
        $field->handle = 'linkUrl';
        $field->types = [Url::id()];
        return $field;
    }
}
