<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\generators\fields;

use craft\base\FieldInterface;
use craft\fields\Categories as CategoriesField;
use craft\wpimport\BaseFieldGenerator;
use craft\wpimport\generators\categorygroups\Categories as CategoryGroup;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Categories extends BaseFieldGenerator
{
    protected static function uid(): string
    {
        return '087a4919-1550-46cc-95e8-e492b43334ee';
    }

    protected static function create(): FieldInterface
    {
        $field = new CategoriesField();
        $field->name = 'Categories';
        $field->handle = 'categories';
        $field->source = sprintf('group:%s', CategoryGroup::get()->uid);
        return $field;
    }
}
