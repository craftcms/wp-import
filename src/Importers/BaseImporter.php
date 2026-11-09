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
use CraftCms\WpImport\Console\ImportCommand;

/**
 * Base importer class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
abstract class BaseImporter
{
    public function __construct(
        protected ImportCommand $command,
    ) {}

    /**
     * Returns the importer’s slug, which is used to identify it from the `--type` option.
     */
    abstract public function slug(): string;

    /**
     * Returns the API URI (sans `wp-json/`) to fetch items from.
     */
    abstract public function apiUri(): string;

    /**
     * Returns the importer’s human-facing label.
     */
    abstract public function label(): string;

    /**
     * Returns any query params that should be sent with API requests.
     */
    public function queryParams(): array
    {
        return [];
    }

    /**
     * Returns the element type imported items will be saved as.
     *
     * @return class-string<ElementInterface>
     */
    abstract public function elementType(): string;

    /**
     * Returns whether the importer is supported.
     */
    public function supported(?string &$reason): bool
    {
        return true;
    }

    /**
     * Returns the query used to find elements that were already imported.
     */
    public function existingElementQuery(): ElementQueryInterface
    {
        return $this->elementType()::find();
    }

    /**
     * Prepares the system for the first import of this type.
     */
    public function prep(): void {}

    /**
     * Attempts to find an existing element for the given item data.
     */
    public function find(array $data): ?ElementInterface
    {
        return null;
    }

    /**
     * Populates an element with the given item data.
     */
    abstract public function populate(ElementInterface $element, array $data): void;
}
