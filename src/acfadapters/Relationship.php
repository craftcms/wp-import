<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\acfadapters;

use craft\base\FieldInterface;
use craft\fields\Checkboxes as CheckboxesField;
use craft\fields\Dropdown;
use craft\fields\Entries;
use craft\fields\RadioButtons;
use craft\wpimport\BaseAcfAdapter;

/**
 * Base block transformer class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Relationship extends BaseAcfAdapter
{
    public static function type(): string
    {
        return 'relationship';
    }

    public function create(array $data): FieldInterface
    {
        $field = new Entries();
        if ($data['min']) {
            $field->minRelations = $data['min'];
        }
        if ($data['max']) {
            $field->maxRelations = $data['max'];
        }
        return $field;
    }

    public function normalizeValue(mixed $value, array $data): mixed
    {
        return array_map(fn(int $id) => $this->command->importPost($id), $value);
    }
}
