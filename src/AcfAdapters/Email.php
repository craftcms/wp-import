<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\AcfAdapters;

use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Email as EmailField;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Email extends BaseAcfAdapter
{
    public static function type(): string
    {
        return 'email';
    }

    public function create(array $data): FieldInterface
    {
        $field = new EmailField;
        $field->placeholder = $data['placeholder'];

        return $field;
    }
}
