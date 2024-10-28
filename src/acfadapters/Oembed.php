<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\acfadapters;

use craft\base\FieldInterface;
use craft\fields\Link;
use craft\fields\linktypes\Url as UrlLinkType;
use craft\wpimport\BaseAcfAdapter;

/**
 * Base block transformer class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Oembed extends BaseAcfAdapter
{
    public static function type(): string
    {
        return 'oembed';
    }

    public function create(array $data): FieldInterface
    {
        $field = new Link();
        $field->types = [UrlLinkType::id()];
        return $field;
    }
}
