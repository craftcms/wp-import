<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\Exceptions;

use CraftCms\Cms\Support\Json;
use RuntimeException;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class UnknownBlockTypeException extends RuntimeException implements ReportableExceptionInterface
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
