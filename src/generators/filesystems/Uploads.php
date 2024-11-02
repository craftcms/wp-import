<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\generators\filesystems;

use craft\base\FsInterface;
use craft\fs\Local;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Uploads extends BaseFsGenerator
{
    protected static function handle(): string
    {
        return 'uploads';
    }

    protected static function create(): FsInterface
    {
        $fs = new Local();
        $fs->name = 'Uploads';
        $fs->path = '@webroot/uploads';
        $fs->hasUrls = true;
        $fs->url = '/uploads';
        return $fs;
    }
}
