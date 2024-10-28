<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\acfadapters;

use craft\base\FieldInterface;
use craft\fields\Lightswitch;
use craft\wpimport\BaseAcfAdapter;

/**
 * Base block transformer class
 *
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
        $field = new Lightswitch();
        $field->default = (bool)$data['default_value'];
        return $field;
    }
}
