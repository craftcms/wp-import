<?php

namespace CraftCms\WpImport\BlockTransformers;

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\WpImport\Generators\EntryTypes\CodepenEmbed as CodepenEmbedEntryType;

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
        return $this->command->createNestedEntry($entry, function (Entry $nestedEntry) use ($data) {
            $nestedEntry->setTypeId(CodepenEmbedEntryType::get()->id);
            $nestedEntry->setFieldValue('penUrl', $data['attrs']['penURL']);
        });
    }
}
