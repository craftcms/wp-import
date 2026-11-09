<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\acfadapters;

use craft\base\FieldInterface;
use craft\fields\Categories;
use craft\fields\Tags;
use craft\wpimport\BaseAcfAdapter;
use craft\wpimport\generators\taggroups\Tags as TagGroup;
use craft\wpimport\importers\Tag;
use craft\wpimport\importers\Taxonomy as TaxonomyImporter;
use Illuminate\Support\Collection;
use Throwable;
use yii\base\NotSupportedException;

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
                throw new NotSupportedException('Taxonomy fields set to “Format” aren’t supported.');
            case 'post_tag':
                $field = new Tags();
                $field->source = sprintf('taggroup:%s', TagGroup::get()->uid);
                return $field;
            default:
                /** @var TaxonomyImporter $importer */
                $importer = $this->command->importers[$data['taxonomy']];
                $field = new Categories();
                $field->source = sprintf('group:%s', $importer->categoryGroup()->uid);
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

        return Collection::make((array)$value)
            ->map(function(int $id) use ($slug) {
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
