<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\generators\fields;

use craft\base\FieldInterface;
use craft\fields\Assets;
use craft\wpimport\BaseFieldGenerator;
use craft\wpimport\generators\volumes\Uploads;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Media extends BaseFieldGenerator
{
    protected static function uid(): string
    {
        return 'a553e2eb-f7c3-4f3e-a07e-27f4c94cec86';
    }

    protected static function create(): FieldInterface
    {
        $sourceKey = sprintf('volume:%s', Uploads::get()->uid);
        $field = new Assets();
        $field->name = 'Media';
        $field->handle = 'media';
        $field->sources = [$sourceKey];
        $field->viewMode = 'large';
        $field->defaultUploadLocationSource = $sourceKey;
        return $field;
    }
}
