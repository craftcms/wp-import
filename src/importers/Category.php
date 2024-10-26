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
    public const NAME = 'category';

    public function name(): string
    {
        return self::NAME;
    }

    public function apiUri(): string
    {
        return 'wp/v2/categories';
    }

    public function label(): string
    {
        return 'Categories';
    }

    public function elementType(): string
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
            $element->setParentId($this->command->import(self::NAME, $data['parent']));
        }
    }
}
