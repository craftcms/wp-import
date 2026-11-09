<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\Events;

use CraftCms\WpImport\AcfAdapters\BaseAcfAdapter;
use Illuminate\Support\Collection;

/**
 * Dispatched when the ACF adapter types are being loaded for an import.
 *
 * Classes must extend {@see BaseAcfAdapter}.
 *
 * ```php
 * use CraftCms\WpImport\Events\AcfAdapterTypesResolving;
 * use Illuminate\Support\Facades\Event;
 *
 * Event::listen(function (AcfAdapterTypesResolving $event) {
 *     $event->types->push(MyAcfAdapter::class);
 * });
 * ```
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class AcfAdapterTypesResolving
{
    public function __construct(
        /** @var Collection<int, string> Class names */
        public Collection $types,
    ) {}
}
