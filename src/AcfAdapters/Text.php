<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\AcfAdapters;

use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\PlainText;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Text extends BaseAcfAdapter
{
    public static function type(): string
    {
        return 'text';
    }

    public function create(array $data): FieldInterface
    {
        $field = new PlainText;
        $field->placeholder = $data['placeholder'];

        return $field;
    }
}
