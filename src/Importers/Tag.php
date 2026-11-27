<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\Importers;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Entry\Elements\Entry;

/**
 * Imports WordPress tags as entries in a “Tags” channel section.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Tag extends BaseImporter
{
    public const SLUG = 'tag';

    private Taxonomy $taxonomy;

    public function slug(): string
    {
        return self::SLUG;
    }

    public function apiUri(): string
    {
        return 'wp/v2/tags';
    }

    public function label(): string
    {
        return 'Tags';
    }

    public function elementType(): string
    {
        return Entry::class;
    }

    public function existingElementQuery(): ElementQueryInterface
    {
        return $this->taxonomy()->existingElementQuery();
    }

    public function populate(ElementInterface $element, array $data): void
    {
        $this->taxonomy()->populate($element, $data);
    }

    /**
     * Returns the taxonomy importer that manages the Tags section and field.
     */
    public function taxonomy(): Taxonomy
    {
        return $this->taxonomy ??= new Taxonomy([
            'slug' => 'post_tag',
            'name' => 'Tags',
            'labels' => ['singular_name' => 'Tag'],
            'hierarchical' => false,
            'rest_namespace' => 'wp/v2',
            'rest_base' => 'tags',
        ], $this->command);
    }
}
