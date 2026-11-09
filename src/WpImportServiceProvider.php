<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport;

use yii\base\BootstrapInterface;
use yii\console\Application as ConsoleApp;

/**
 * WP Import Yii2 Extension
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Extension implements BootstrapInterface
{
    public function bootstrap($app)
    {
        if ($app instanceof ConsoleApp) {
            $app->controllerMap['wp-import'] = Command::class;
        }
    }
}
