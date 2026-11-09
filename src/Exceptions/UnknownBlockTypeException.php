<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\errors;

use craft\helpers\Json;
use yii\console\Exception;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class UnknownBlockTypeException extends Exception implements ReportableExceptionInterface
{
    public function __construct(
        public readonly string $blockType,
        public readonly array $data,
    ) {
        parent::__construct($this->getName());
    }

    public function getName(): string
    {
        return "Unknown block type: $this->blockType";
    }

    public function getReport(): string
    {
        return sprintf(
            "Block data:\n%s",
            Json::encode($this->data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        );
    }
}
