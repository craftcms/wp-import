<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport;

use craft\base\FieldInterface;
use yii\base\BaseObject;

/**
 * Base block transformer class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
abstract class BaseAcfAdapter extends BaseObject
{
    /**
     * Returns the ACF field type this generator is for.
     *
     * @return string
     */
    abstract public static function type(): string;

    public function __construct(
        protected Command $command,
        array $config = [],
    ) {
        parent::__construct($config);
    }

    /**
     * Returns the resolved field.
     *
     * @param array $data The ACF field data
     * @return FieldInterface
     */
    abstract public function create(array $data): FieldInterface;

    /**
     * Normalizes a field’s value.
     *
     * @param mixed $value The field value
     * @param array $data The ACF field data
     */
    public function normalizeValue(mixed $value, array $data): mixed
    {
        return $value;
    }
}
