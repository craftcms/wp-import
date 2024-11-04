<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\acfadapters;

use craft\base\FieldInterface;
use craft\fields\Users;
use craft\wpimport\BaseAcfAdapter;
use craft\wpimport\importers\User as UserImporter;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class User extends BaseAcfAdapter
{
    public static function type(): string
    {
        return 'user';
    }

    public function create(array $data): FieldInterface
    {
        $field = new Users();
        if (!$data['multiple']) {
            $field->maxRelations = 1;
        }
        return $field;
    }

    public function normalizeValue(mixed $value, array $data): mixed
    {
        return array_map(
            fn(int $id) => $this->command->import(UserImporter::SLUG, $id),
            (array)$value,
        );
    }
}
