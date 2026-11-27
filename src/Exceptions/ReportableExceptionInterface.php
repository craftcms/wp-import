<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\Exceptions;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
interface ReportableExceptionInterface
{
    public function getReport(): string;
}
