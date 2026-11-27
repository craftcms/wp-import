<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\BlockTransformers;

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\WpImport\Generators\EntryTypes\Video as VideoEntryType;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class VideoPressVideo extends BaseBlockTransformer
{
    public static function blockName(): string
    {
        return 'videopress/video';
    }

    public function render(array $data, Entry $entry): string
    {
        return $this->command->createNestedEntry($entry, function (Entry $nestedEntry) use ($data) {
            $nestedEntry->setTypeId(VideoEntryType::get()->id);
            $nestedEntry->setFieldValue('videoUrl', $data['attrs']['src']);
        });
    }
}
