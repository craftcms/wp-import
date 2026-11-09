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
class TextArea extends BaseAcfAdapter
{
    public static function type(): string
    {
        return 'textarea';
    }

    public function create(array $data): FieldInterface
    {
        $field = new PlainText;
        $field->placeholder = $data['placeholder'];
        $field->multiline = true;
        if ($data['rows']) {
            $field->initialRows = $data['rows'];
        }

        return $field;
    }
}
