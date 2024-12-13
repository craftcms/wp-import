<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\generators\fields;

use craft\base\FieldInterface;
use craft\ckeditor\Field;
use craft\wpimport\generators\ckeconfigs\PostContent as PostContentConfig;
use craft\wpimport\generators\ckeconfigs\PullQuote as PullQuoteConfig;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class PullQuote extends BaseFieldGenerator
{
    protected static function uid(): string
    {
        return 'cdbd7117-1123-4e4b-b255-dd1c92d3c864';
    }

    protected static function create(): FieldInterface
    {
        $field = new Field();
        $field->name = 'Pull Quote';
        $field->handle = 'pullQuote';
        $field->ckeConfig = PullQuoteConfig::get()->uid;
        $field->searchable = true;
        return $field;
    }
}
