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
class VideoPressVideo extends BaseBlockTransformer
{
    public static function blockName(): string
    {
        return 'videopress/video';
    }

    public function render(array $data, Entry $entry): string
    {
        return $this->createNestedEntry($entry, function(Entry $nestedEntry) use ($data) {
            $nestedEntry->setTypeId($this->command->videoEntryType->id);
            $nestedEntry->setFieldValue('videoUrl', $data['attrs']['src']);
        });
    }
}
