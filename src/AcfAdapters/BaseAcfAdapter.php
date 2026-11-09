<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\AcfAdapters;

use CraftCms\Cms\Asset\Conditions\AssetCondition;
use CraftCms\Cms\Asset\Conditions\FileTypeConditionRule;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Field\Assets;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\WpImport\Console\ImportCommand;

/**
 * Base ACF adapter class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
abstract class BaseAcfAdapter
{
    /**
     * Returns the ACF field type this adapter handles.
     */
    abstract public static function type(): string;

    public function __construct(
        protected ImportCommand $command,
    ) {}

    /**
     * Creates a field (but doesn’t save it).
     *
     * @param  array  $data  The ACF field data
     */
    abstract public function create(array $data): FieldInterface;

    /**
     * Normalizes a field’s value.
     *
     * @param  mixed  $value  The value from the REST API
     * @param  array  $data  The ACF field data
     */
    public function normalizeValue(mixed $value, array $data): mixed
    {
        return $value;
    }

    /**
     * Restricts the file kinds an Assets field allows, via its selection condition.
     *
     * @param  string[]  $kinds
     */
    protected function restrictFileKinds(Assets $field, array $kinds): void
    {
        $condition = $field->getSelectionCondition() ?? new AssetCondition(Asset::class);
        $condition->addConditionRule(new FileTypeConditionRule(['values' => $kinds]));
        $field->setSelectionCondition($condition);
    }
}
