<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\errors;

use Throwable;
use yii\console\Exception;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class ImportException extends Exception
{
    public function __construct(
        public readonly string $slug,
        public readonly int $itemId,
        ?Throwable $previous = null,
    ) {
        parent::__construct($previous?->getMessage() ?? $this->getName(), previous: $previous);
    }

    public function getName(): string
    {
        return 'Import error';
    }
}
