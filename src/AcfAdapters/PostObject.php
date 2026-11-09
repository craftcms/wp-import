<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\AcfAdapters;

use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Entries;
use CraftCms\WpImport\Importers\PostType;
use RuntimeException;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class PostObject extends BaseAcfAdapter
{
    public static function type(): string
    {
        return 'post_object';
    }

    public function create(array $data): FieldInterface
    {
        $field = new Entries;
        $field->sources = array_map(function (string $type) use ($data) {
            $importer = $this->command->importers[$type] ?? null;
            if (! $importer instanceof PostType) {
                throw new RuntimeException("Unsupported post type in the {$data['label']} field: $type");
            }

            return sprintf('section:%s', $importer->section()->uid);
        }, (array) $data['post_type']);
        $field->maxRelations = 1;

        return $field;
    }

    public function normalizeValue(mixed $value, array $data): mixed
    {
        if (count($data['post_type']) === 1) {
            $type = reset($data['post_type']);
            $id = $this->command->import($type, $value);
        } else {
            $id = $this->command->importPost($value);
        }

        return [$id];
    }
}
