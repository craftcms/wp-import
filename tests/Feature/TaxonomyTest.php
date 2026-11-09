<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\WpImport\Generators\Fields\WpId;
use CraftCms\WpImport\Importers\Taxonomy;

function taxonomy(string $slug, string $name, bool $hierarchical): Taxonomy
{
    return new Taxonomy([
        'slug' => $slug,
        'name' => $name,
        'labels' => ['singular_name' => rtrim($name, 's')],
        'hierarchical' => $hierarchical,
        'rest_namespace' => 'wp/v2',
        'rest_base' => $slug,
    ], importCommand());
}

it('imports hierarchical taxonomies into a structure and flat ones into a channel', function () {
    expect(taxonomy('category', 'Categories', true)->section()->type)->toBe(SectionType::Structure)
        ->and(taxonomy('genre', 'Genres', false)->section()->type)->toBe(SectionType::Channel);
});

it('relates posts to terms with an Entries field limited to the taxonomy’s section', function () {
    $taxonomy = taxonomy('category', 'Categories', true);
    $field = $taxonomy->field();

    expect($field)->toBeInstanceOf(Entries::class)
        ->and($field->sources)->toBe([sprintf('section:%s', $taxonomy->section()->uid)]);
});

it('only finds already-imported terms within the taxonomy’s own section', function () {
    $genres = taxonomy('genre', 'Genres', false);
    $categories = taxonomy('category', 'Categories', true);

    // WordPress post and term IDs overlap, so an entry elsewhere can share a term’s WordPress ID
    $genre = new Entry;
    $genres->populate($genre, ['name' => 'Fiction', 'slug' => 'fiction', 'description' => '']);
    $genre->siteId = Sites::getPrimarySite()->id;
    $genre->setFieldValue(WpId::get()->handle, 5);
    expect(Elements::saveElement($genre))->toBeTrue();

    $wpIdHandle = WpId::get()->handle;

    expect($categories->existingElementQuery()->$wpIdHandle(5)->status(null)->one())->toBeNull()
        ->and($genres->existingElementQuery()->$wpIdHandle(5)->status(null)->one()?->id)->toBe($genre->id);
});
