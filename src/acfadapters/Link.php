<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\acfadapters;

use craft\base\FieldInterface;
use craft\fields\Link as LinkField;
use craft\fields\linktypes\Entry;
use craft\fields\linktypes\Url;
use craft\wpimport\BaseAcfAdapter;

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
        $field = new LinkField();
        $field->types = [Url::id(), Entry::id()];
        $field->showLabelField = true;
        $field->showTargetField = true;
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
