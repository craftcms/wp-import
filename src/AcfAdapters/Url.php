<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\AcfAdapters;

use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Link;
use CraftCms\Cms\Field\LinkTypes\Url as UrlLinkType;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Url extends BaseAcfAdapter
{
    public static function type(): string
    {
        return 'url';
    }

    public function create(array $data): FieldInterface
    {
        $field = new Link;
        $field->types = [UrlLinkType::id()];

        return $field;
    }
}
