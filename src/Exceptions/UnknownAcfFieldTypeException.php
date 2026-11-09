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
class UnknownAcfFieldTypeException extends RuntimeException implements ReportableExceptionInterface
{
    public function __construct(
        public readonly string $fieldType,
        public readonly array $data,
    ) {
        parent::__construct($this->getName());
    }

    public function getName(): string
    {
        return "Unknown ACF field type: $this->fieldType";
    }

    public function getReport(): string
    {
        return sprintf(
            "Field data:\n%s",
            Json::encode($this->data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        );
    }
}
