<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\AcfAdapters;

use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Lightswitch;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class TrueFalse extends BaseAcfAdapter
{
    public static function type(): string
    {
        return 'true_false';
    }

    public function create(array $data): FieldInterface
    {
        $field = new Lightswitch;
        $field->default = (bool) $data['default_value'];

        return $field;
    }
}
