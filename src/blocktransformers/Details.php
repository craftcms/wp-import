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
class Details extends BaseBlockTransformer
{
    public static function blockName(): string
    {
        return 'core/details';
    }

    public function render(array $data, Entry $entry): string
    {
        return $this->createNestedEntry($entry, function(Entry $nestedEntry) use ($data) {
            $nestedEntry->setTypeId($this->command->detailsEntryType->id);
            $nestedEntry->setFieldValue($this->command->summaryField->handle, $data['attrs']['summary']);

            // save it so we get an ID, before parsing the nested blocks
            $this->saveNestedEntry($nestedEntry);

            $nestedEntry->setFieldValue(
                'postContent',
                $this->command->renderBlocks($data['innerBlocks'], $nestedEntry)
            );
        });
    }
}
