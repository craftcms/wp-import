<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\BlockTransformers;

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\WpImport\Generators\EntryTypes\Group as GroupEntryType;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Group extends BaseBlockTransformer
{
    public static function blockName(): string
    {
        return 'core/group';
    }

    public function render(array $data, Entry $entry): string
    {
        return $this->command->createNestedEntry($entry, function (Entry $nestedEntry) use ($data) {
            $nestedEntry->setTypeId(GroupEntryType::get()->id);
            $nestedEntry->setFieldValue(
                'backgroundColor',
                $this->command->normalizeColor(
                    $data['attrs']['style']['color']['background']
                    ?? $data['attrs']['backgroundColor']
                    ?? null
                ),
            );
            $nestedEntry->setFieldValue(
                'textColor',
                $this->command->normalizeColor(
                    $data['attrs']['style']['color']['text'] ??
                    $data['attrs']['textColor']
                    ?? null
                ),
            );

            // save it so we get an ID, before parsing the nested blocks
            $this->command->saveNestedEntry($nestedEntry);

            $nestedEntry->setFieldValue(
                'postContent',
                $this->command->renderBlocks($data['innerBlocks'], $nestedEntry),
            );
        });
    }
}
