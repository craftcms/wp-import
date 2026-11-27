<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\BlockTransformers;

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Support\Html;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class ItemList extends BaseBlockTransformer
{
    public static function blockName(): string
    {
        return 'core/list';
    }

    public function render(array $data, Entry $entry): string
    {
        if (! $data['innerBlocks']) {
            return $data['innerHTML'];
        }

        $tag = ! empty($data['attrs']['ordered']) ? 'ol' : 'ul';
        $content = $this->command->renderBlocks($data['innerBlocks'], $entry);

        return Html::tag($tag, $content, [
            'reversed' => ! empty($data['attrs']['reversed']),
            'start' => $data['attrs']['start'] ?? false,
            'type' => match ($data['attrs']['type'] ?? null) {
                'upper-alpha' => 'A',
                'lower-alpha' => 'a',
                'upper-roman' => 'I',
                'lower-roman' => 'i',
                default => false,
            },
        ]);
    }
}
