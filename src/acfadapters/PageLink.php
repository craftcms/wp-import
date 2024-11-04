<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\acfadapters;

use craft\base\FieldInterface;
use craft\fields\Entries;
use craft\wpimport\BaseAcfAdapter;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class PageLink extends BaseAcfAdapter
{
    public static function type(): string
    {
        return 'page_link';
    }

    public function create(array $data): FieldInterface
    {
        $field = new Entries();
        if (!$data['multiple']) {
            $field->maxRelations = 1;
        }
        return $field;
    }

    public function normalizeValue(mixed $value, array $data): mixed
    {
        return array_map(
            fn(int $id) => $this->command->importPost($id),
            (array)$value,
        );
    }
}
