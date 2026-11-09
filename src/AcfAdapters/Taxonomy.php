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
use CraftCms\WpImport\Importers\Tag;
use CraftCms\WpImport\Importers\Taxonomy as TaxonomyImporter;
use Illuminate\Support\Collection;
use RuntimeException;
use Throwable;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Taxonomy extends BaseAcfAdapter
{
    public static function type(): string
    {
        return 'taxonomy';
    }

    public function create(array $data): FieldInterface
    {
        switch ($data['taxonomy']) {
            case 'post_format':
                // We don't have a way to resolve the format IDs from normalizeValue()
                throw new RuntimeException('Taxonomy fields set to “Format” aren’t supported.');
            case 'post_tag':
                /** @var Tag $tagImporter */
                $tagImporter = $this->command->importers[Tag::SLUG];
                $field = new Entries;
                $field->sources = [sprintf('section:%s', $tagImporter->taxonomy()->section()->uid)];

                return $field;
            default:
                /** @var TaxonomyImporter $importer */
                /** @var TaxonomyImporter $importer */
                $importer = $this->command->importers[$data['taxonomy']];
                $field = new Entries;
                $field->sources = [sprintf('section:%s', $importer->section()->uid)];
                if (in_array($data['field_type'], ['select', 'radio'])) {
                    $field->maxRelations = 1;
                }

                return $field;
        }
    }

    public function normalizeValue(mixed $value, array $data): mixed
    {
        $slug = match ($data['taxonomy']) {
            'post_tag' => Tag::SLUG,
            default => $data['taxonomy'],
        };

        return Collection::make((array) $value)
            ->map(function (int $id) use ($slug) {
                try {
                    return $this->command->import($slug, $id);
                } catch (Throwable) {
                    return null;
                }
            })
            ->filter()
            ->all();
    }
}
