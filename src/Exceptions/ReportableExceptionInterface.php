<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\errors;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
interface ReportableExceptionInterface
{
    public function getReport(): string;
}
