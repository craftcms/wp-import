<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\blocktransformers;

use craft\elements\Entry;
use craft\wpimport\BaseBlockTransformer;

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
        return $this->createNestedEntry($entry, function(Entry $nestedEntry) use ($data) {
            $nestedEntry->setTypeId($this->command->groupEntryType->id);
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
            $this->saveNestedEntry($nestedEntry);

            $nestedEntry->setFieldValue(
                'postContent',
                $this->command->renderBlocks($data['innerBlocks'], $nestedEntry),
            );
        });
    }
}
