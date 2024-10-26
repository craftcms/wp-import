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
use Illuminate\Support\Collection;
use Throwable;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Slideshow extends BaseBlockTransformer
{
    public static function blockName(): string
    {
        return 'jetpack/slideshow';
    }

    public function render(array $data, Entry $entry): string
    {
        $assetIds = Collection::make($data['attrs']['ids'])
            ->map(function(int $id) {
                try {
                    return $this->command->import(Media::NAME, $id);
                } catch (Throwable) {
                    return null;
                }
            })
            ->filter()
            ->all();

        if (empty($assetIds)) {
            return '';
        }

        return $this->createNestedEntry($entry, function(Entry $nestedEntry) use ($assetIds) {
            $nestedEntry->setTypeId(MediaEntryType::get()->id);
            $nestedEntry->setFieldValue(MediaField::get()->handle, $assetIds);
        });
    }
}
