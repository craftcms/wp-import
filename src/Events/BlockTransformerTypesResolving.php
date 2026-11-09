<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\Events;

use CraftCms\WpImport\BlockTransformers\BaseBlockTransformer;
use Illuminate\Support\Collection;

/**
 * Dispatched when the block transformer types are being loaded for an import.
 *
 * Classes must extend {@see BaseBlockTransformer}.
 *
 * ```php
 * use CraftCms\WpImport\Events\BlockTransformerTypesResolving;
 * use Illuminate\Support\Facades\Event;
 *
 * Event::listen(function (BlockTransformerTypesResolving $event) {
 *     $event->types->push(MyBlockTransformer::class);
 * });
 * ```
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class BlockTransformerTypesResolving
{
    public function __construct(
        /** @var Collection<int, string> Class names */
        public Collection $types,
    ) {}
}
