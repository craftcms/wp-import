<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\Generators\Fields;

use CraftCms\Cms\Field\Assets;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\WpImport\Generators\Volumes\WpContent;

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
        $sourceKey = sprintf('volume:%s', WpContent::get()->uid);
        $field = new Assets;
        $field->name = 'Media';
        $field->handle = 'media';
        $field->sources = [$sourceKey];
        $field->viewMode = 'large';
        $field->defaultUploadLocationSource = $sourceKey;

        return $field;
    }
}
