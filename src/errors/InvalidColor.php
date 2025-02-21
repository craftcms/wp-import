<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\errors;

use yii\console\Exception;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class InvalidColor extends Exception implements ReportableExceptionInterface
{
    public function __construct(
        public readonly string $color,
    ) {
        parent::__construct($this->getName());
    }

    public function getName(): string
    {
        return "Invalid color: $this->color";
    }

    public function getReport(): string
    {
        return '';
    }
}
