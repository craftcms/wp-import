<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\blocktransformers;

use craft\elements\Entry;
use craft\wpimport\BaseBlockTransformer;
use craft\wpimport\generators\entrytypes\Media as MediaEntryType;
use craft\wpimport\generators\fields\Media as MediaField;
use craft\wpimport\importers\Media;
use Throwable;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Image extends BaseBlockTransformer
{
    public static function blockName(): string
    {
        return 'core/image';
    }

    public function render(array $data, Entry $entry): string
    {
        $id = $data['attrs']['id'] ?? null;
        if (!$id) {
            return '';
        }

        try {
            $assetId = $this->command->import(Media::resource(), $id);
        } catch (Throwable) {
            return '';
        }

        return $this->createNestedEntry($entry, function(Entry $nestedEntry) use ($assetId) {
            $nestedEntry->setTypeId(MediaEntryType::get()->id);
            $nestedEntry->setFieldValue(MediaField::get()->handle, [$assetId]);
        });
    }
}
