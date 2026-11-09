<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\Events;

use CraftCms\WpImport\Importers\BaseImporter;
use Illuminate\Support\Collection;

/**
 * Dispatched when the importer types are being loaded for an import.
 *
 * Classes must extend {@see BaseImporter}.
 *
 * ```php
 * use CraftCms\WpImport\Events\ImporterTypesResolving;
 * use Illuminate\Support\Facades\Event;
 *
 * Event::listen(function (ImporterTypesResolving $event) {
 *     $event->types->push(MyImporter::class);
 * });
 * ```
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class ImporterTypesResolving
{
    public function __construct(
        /** @var Collection<int, string> Class names */
        public Collection $types,
    ) {}
}
