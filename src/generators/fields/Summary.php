<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\generators\fields;

use craft\base\FieldInterface;
use craft\fields\PlainText;
use craft\wpimport\BaseFieldGenerator;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Summary extends BaseFieldGenerator
{
    protected static function uid(): string
    {
        return 'dee725db-134a-40f3-bd54-199353365c57';
    }

    protected static function create(): FieldInterface
    {
        $field = new PlainText();
        $field->name = 'Summary';
        $field->handle = 'summary';
        $field->searchable = true;
        return $field;
    }
}
