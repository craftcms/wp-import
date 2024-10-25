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
    /**
     * Returns the resource name (e.g. `posts`).
     *
     * @return string
     */
    abstract public static function resource(): string;

    /**
     * Returns query params that should be passed to the API.
     *
     * @return array
     */
    public static function queryParams(): array
    {
        return [];
    }

    /**
     * Returns the element type imported resources will resolve to.
     *
     * @return string
     * @phpstan-return class-string<ElementInterface>
     */
    abstract public static function elementType(): string;

    public function __construct(
        protected Command $command,
        array $config = [],
    ) {
        parent::__construct($config);
    }

    /**
     * Returns whether importing this resource is supported.
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
     * @param array $data The resource data
     */
    abstract public function populate(ElementInterface $element, array $data): void;
}
