<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\BlockTransformers;

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\WpImport\Console\ImportCommand;

/**
 * Base block transformer class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
abstract class BaseBlockTransformer
{
    /**
     * Returns the block name this transformer handles (e.g. `core/paragraph`).
     */
    abstract public static function blockName(): string;

    public function __construct(
        protected ImportCommand $command,
    ) {}

    /**
     * Renders a block’s HTML.
     *
     * @param  array  $data  The block data
     * @param  Entry  $entry  The entry being imported
     */
    abstract public function render(array $data, Entry $entry): string;

    /**
     * Creates a nested entry.
     *
     * @return string The `<craft-entry>` tag to be returned by `render()`
     */
    protected function createNestedEntry(Entry $entry, callable $populate): string
    {
        return $this->command->createNestedEntry($entry, $populate);
    }

    /**
     * Saves a nested entry.
     */
    protected function saveNestedEntry(Entry $entry): void
    {
        $this->command->saveNestedEntry($entry);
    }
}
