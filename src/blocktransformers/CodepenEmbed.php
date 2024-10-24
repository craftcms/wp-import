<?php

namespace craft\wpimport\blocktransformers;

use craft\elements\Entry;
use craft\wpimport\BaseBlockTransformer;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class CodepenEmbed extends BaseBlockTransformer
{
    public static function blockName(): string
    {
        return 'cp/codepen-gutenberg-embed-block';
    }

    public function render(array $data, Entry $entry): string
    {
        return $this->createNestedEntry($entry, function(Entry $nestedEntry) use ($data) {
            $nestedEntry->setTypeId($this->command->codepenEntryType->id);
            $nestedEntry->setFieldValue('penUrl', $data['attrs']['penURL']);
        });
    }
}
