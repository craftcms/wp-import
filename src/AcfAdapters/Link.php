<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\AcfAdapters;

use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Link as LinkField;
use CraftCms\Cms\Field\LinkTypes\Entry;
use CraftCms\Cms\Field\LinkTypes\Url;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Link extends BaseAcfAdapter
{
    public static function type(): string
    {
        return 'link';
    }

    public function create(array $data): FieldInterface
    {
        // Craft 6 maps this to `advancedFields` from the constructor config
        $field = new LinkField([
            'showTargetField' => true,
        ]);
        $field->types = [Url::id(), Entry::id()];
        $field->showLabelField = true;

        return $field;
    }

    public function normalizeValue(mixed $value, array $data): mixed
    {
        return [
            'value' => $value['url'],
            'type' => Url::id(),
            'label' => $value['title'],
            'target' => $value['target'] ?: null,
        ];
    }
}
