<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\generators\fields;

use craft\base\FieldInterface;
use craft\fields\Tags as TagsField;
use craft\wpimport\BaseFieldGenerator;
use craft\wpimport\generators\taggroups\Tags as TagGroup;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Tags extends BaseFieldGenerator
{
    protected static function uid(): string
    {
        return '5a524179-b073-4ec2-a219-674143dbc455';
    }

    protected static function create(): FieldInterface
    {
        $field = new TagsField();
        $field->name = 'Tags';
        $field->handle = 'tags';
        $field->source = sprintf('taggroup:%s', TagGroup::get()->uid);
        return $field;
    }
}
