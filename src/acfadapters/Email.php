<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\acfadapters;

use craft\base\FieldInterface;
use craft\fields\Email as EmailField;
use craft\wpimport\BaseAcfAdapter;

/**
 * Base block transformer class
 *
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
        $field = new EmailField();
        $field->placeholder = $data['placeholder'];
        return $field;
    }
}
