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

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class PostContent extends BaseFieldGenerator
{
    protected static function uid(): string
    {
        return 'e314b2cd-4ad3-4d8c-94d8-347ddb16245d';
    }

    protected static function create(): FieldInterface
    {
        $field = new Field();
        $field->name = 'Post Content';
        $field->handle = 'postContent';
        $field->ckeConfig = PostContentConfig::get()->uid;
        $field->purifyHtml = false; // todo: allow video embeds in purifier config
        $field->searchable = true;
        return $field;
    }
}
