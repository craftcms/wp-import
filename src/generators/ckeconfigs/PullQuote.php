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
class PullQuote extends BaseCkeConfigGenerator
{
    protected static function uid(): string
    {
        return 'ab558ffb-1a0a-4153-bd95-0106e9e7c5f3';
    }

    protected static function populate(CkeConfig $config): void
    {
        $config->name = 'Pull Quote';
        $config->toolbar = [
            'heading', 'bold', 'italic', 'link', '|',
            'code', 'strikethrough', 'subscript', 'superscript',
        ];
    }
}
