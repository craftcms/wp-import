<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\importers;

use craft\base\ElementInterface;
use craft\elements\Tag as TagElement;
use craft\wpimport\BaseImporter;
use craft\wpimport\generators\taggroups\Tags;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Tag extends BaseImporter
{
    public const RESOURCE = 'tags';

    public function resource(): string
    {
        return self::RESOURCE;
    }

    public function label(): string
    {
        return 'Tags';
    }

    public function elementType(): string
    {
        return TagElement::class;
    }

    public function populate(ElementInterface $element, array $data): void
    {
        /** @var TagElement $element */
        $element->groupId = Tags::get()->id;
        $element->title = $data['name'];
        $element->slug = $data['slug'];
    }
}
