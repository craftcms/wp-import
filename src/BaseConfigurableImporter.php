<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport;

use craft\base\Element;
use craft\base\ElementInterface;
use yii\base\BaseObject;

/**
 * Base importer class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
abstract class BaseConfigurableImporter extends BaseImporter
{
    /**
     * Returns the content type label (e.g. `Post Type`).
     */
    abstract public function typeLabel(): string;
}
