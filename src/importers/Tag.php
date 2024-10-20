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

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Tag extends BaseImporter
{
    public static function resource(): string
    {
        return 'tags';
    }

    public static function elementType(): string
    {
        return TagElement::class;
    }

    public function populate(ElementInterface $element, array $data): void
    {
        /** @var TagElement $element */
        $element->groupId = $this->command->tagGroup->id;
        $element->title = $data['name'];
        $element->slug = $data['slug'];
    }
}
