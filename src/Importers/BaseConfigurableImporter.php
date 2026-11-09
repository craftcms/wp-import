<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\Importers;

/**
 * Base configurable importer class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
abstract class BaseConfigurableImporter extends BaseImporter
{
    /**
     * Returns the type label.
     */
    abstract public function typeLabel(): string;
}
