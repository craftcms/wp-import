<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\generators\taggroups;

use Craft;
use craft\helpers\StringHelper;
use craft\models\TagGroup;
use craft\wpimport\Command;
use yii\console\Exception;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
abstract class BaseTagGroupGenerator
{
    public static function get(): TagGroup
    {
        $group = Craft::$app->tags->getTagGroupByUid(static::uid());

        if (!$group) {
            $group = new TagGroup();
            $group->uid = static::uid();
            static::populate($group);

            if (Craft::$app->tags->getTagGroupByHandle($group->handle) !== null) {
                $group->handle .= '_' . StringHelper::randomString(5);
            }

            /** @var Command $command */
            $command = Craft::$app->controller;
            $command->do("Creating `$group->name` tag group", function() use ($group) {
                if (!Craft::$app->tags->saveTagGroup($group)) {
                    throw new Exception(implode(', ', $group->getFirstErrors()));
                }
            });
        }

        return $group;
    }

    /**
     * Returns the tag group’s UUID.
     */
    abstract protected static function uid(): string;

    /**
     * Creates and configures the tag group (but doesn’t save it).
     */
    abstract protected static function populate(TagGroup $group): void;
}
