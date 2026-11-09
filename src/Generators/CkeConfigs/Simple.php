<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\generators\ckeconfigs;

use craft\ckeditor\CkeConfig;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Simple extends BaseCkeConfigGenerator
{
    protected static function uid(): string
    {
        return '040109cb-42b8-4100-a6f4-c1bd47d55c99';
    }

    protected static function populate(CkeConfig $config): void
    {
        $config->name = 'Pull Quote';
        $config->toolbar = ['bold', 'italic', 'link'];
    }
}
