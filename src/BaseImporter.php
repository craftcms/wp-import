<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport;

use craft\base\Element;
use craft\base\ElementInterface;
use yii\base\BaseObject;

/**
 * Base importer class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
abstract class BaseImporter extends BaseObject
{
    public function __construct(
        protected Command $command,
        array $config = [],
    ) {
        parent::__construct($config);
    }

    /**
     * Returns the content type slug (e.g. `posts`).
     *
     * @return string
     */
    abstract public function slug(): string;

    /**
     * Returns the REST API URI for this content type.
     */
    abstract public function apiUri(): string;

    /**
     * Returns the content type label (e.g. `Posts`).
     */
    abstract public function label(): string;

    /**
     * Returns query params that should be passed to the API.
     *
     * @return array
     */
    public function queryParams(): array
    {
        return [];
    }

    /**
     * Returns the element type imported items will resolve to.
     *
     * @return string
     * @phpstan-return class-string<ElementInterface>
     */
    abstract public function elementType(): string;

    /**
     * Returns whether importing this content type is supported.
     *
     * @param string|null $reason
     * @return bool
     */
    public function supported(?string &$reason): bool
    {
        return true;
    }

    /**
     * Prepares the system to import items of this type.
     */
    public function prep(): void
    {
    }

    /**
     * Returns an existing element based on the given data, if there is one.
     *
     * @param array $data
     * @return ElementInterface|null
     */
    public function find(array $data): ?ElementInterface
    {
        return null;
    }

    /**
     * Populates an element with the given API data.
     *
     * @param ElementInterface $element The element to populate
     * @param array $data The item data
     */
    abstract public function populate(ElementInterface $element, array $data): void;
}
