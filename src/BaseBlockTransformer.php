<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport;

use Craft;
use craft\base\Element;
use craft\elements\Entry;
use craft\wpimport\generators\fields\PostContent;
use yii\base\BaseObject;
use yii\console\Exception;

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
     */
    protected function createNestedEntry(Entry $entry, callable $populate): string
    {
        $nestedEntry = new Entry();
        $nestedEntry->fieldId = PostContent::get()->id;
        $nestedEntry->ownerId = $entry->id;
        $populate($nestedEntry);
        $this->saveNestedEntry($nestedEntry);
        return sprintf('<craft-entry data-entry-id="%s">&nbsp;</craft-entry>', $nestedEntry->id);
    }

    /**
     * Saves a nested entry.
     *
     * @param Entry $entry
     */
    protected function saveNestedEntry(Entry $entry): void
    {
        $entry->setScenario(Element::SCENARIO_ESSENTIALS);
        if (!Craft::$app->elements->saveElement($entry)) {
            throw new Exception(sprintf(
                'Could not save nested %s entry: %s',
                $entry->getType()->name,
                implode(', ', $entry->getFirstErrors())
            ));
        }
    }
}
