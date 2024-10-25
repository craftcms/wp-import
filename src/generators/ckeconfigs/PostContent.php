<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\generators\ckeconfigs;

use craft\ckeditor\CkeConfig;
use craft\wpimport\BaseCkeConfigGenerator;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class PostContent extends BaseCkeConfigGenerator
{
    protected static function uid(): string
    {
        return '7261a233-a194-4374-8a55-91770cef7528';
    }

    protected static function populate(CkeConfig $config): void
    {
        $config->name = 'Post Content';
        $config->toolbar = [
            'heading', '|',
            'bold', 'italic', 'link', '|',
            'blockQuote', 'bulletedList', 'numberedList', 'codeBlock', '|',
            'insertTable', 'mediaEmbed', 'htmlEmbed', 'pageBreak', '|',
            'createEntry', 'sourceEditing',
        ];
    }
}
