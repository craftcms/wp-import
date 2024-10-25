<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\importers;

use craft\base\ElementInterface;
use craft\elements\Category as CategoryElement;
use craft\wpimport\BaseImporter;
use craft\wpimport\generators\categorygroups\Categories;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Category extends BaseImporter
{
    public static function resource(): string
    {
        return 'categories';
    }

    public static function elementType(): string
    {
        return CategoryElement::class;
    }

    public function populate(ElementInterface $element, array $data): void
    {
        /** @var CategoryElement $element */
        $element->groupId = Categories::get()->id;
        $element->title = $data['name'];
        $element->slug = $data['slug'];

        if ($data['parent']) {
            $element->setParentId($this->command->import(static::resource(), $data['parent']));
        }
    }
}
