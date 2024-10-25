<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport;

use Craft;
use craft\helpers\StringHelper;
use craft\models\CategoryGroup;
use yii\console\Exception;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
abstract class BaseCategoryGroupGenerator
{
    public static function get(): CategoryGroup
    {
        $group = Craft::$app->categories->getGroupByUid(static::uid());

        if (!$group) {
            $group = new CategoryGroup();
            $group->uid = static::uid();
            static::populate($group);

            if (Craft::$app->categories->getGroupByHandle($group->handle) !== null) {
                $group->handle .= '_' . StringHelper::randomString(5);
            }

            /** @var Command $command */
            $command = Craft::$app->controller;
            $command->do("Creating `$group->name` category group", function() use ($group) {
                if (!Craft::$app->categories->saveGroup($group)) {
                    throw new Exception(implode(', ', $group->getFirstErrors()));
                }
            });
        }

        return $group;
    }

    /**
     * Returns the category group’s UUID.
     */
    abstract protected static function uid(): string;

    /**
     * Creates and configures the category group (but doesn’t save it).
     */
    abstract protected static function populate(CategoryGroup $group): void;
}
