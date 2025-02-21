<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport;

use Craft;
use craft\elements\Entry;
use yii\base\BaseObject;

/**
 * Base block transformer class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
abstract class BaseBlockTransformer extends BaseObject
{
    /**
     * Returns the block name identified by the parsed block data.
     *
     * @return string
     */
    abstract public static function blockName(): string;

    public function __construct(
        protected Command $command,
        array $config = [],
    ) {
        parent::__construct($config);
    }

    /**
     * Renders the given block data.
     *
     * @param array $data
     * @param Entry $entry
     * @return string
     */
    abstract public function render(array $data, Entry $entry): string;

    /**
     * Creates a nested entry.
     *
     * @param Entry $entry
     * @return string The `<craft-entry>` tag to be returned by `render()`
     * @deprecated
     */
    protected function createNestedEntry(Entry $entry, callable $populate): string
    {
        return $this->command->createNestedEntry($entry, $populate);
    }

    /**
     * Saves a nested entry.
     *
     * @param Entry $entry
     * @deprecated
     */
    protected function saveNestedEntry(Entry $entry): void
    {
        $this->command->saveNestedEntry($entry);
    }
}
