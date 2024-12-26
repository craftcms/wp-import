<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\generators\taggroups;

use Craft;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;
use craft\models\TagGroup;
use craft\wpimport\Command;
use yii\console\Exception;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
abstract class BaseTagGroupGenerator
{
    private static array $tagGroups = [];

    public static function get(): TagGroup
    {
        if (isset(self::$tagGroups[static::uid()])) {
            return self::$tagGroups[static::uid()];
        }

        $group = Craft::$app->tags->getTagGroupByUid(static::uid());
        $newGroup = !$group;

        if ($newGroup) {
            $group = new TagGroup();
            $group->uid = static::uid();
            static::populate($group);

            if (Craft::$app->tags->getTagGroupByHandle($group->handle) !== null) {
                $group->handle .= '_' . StringHelper::randomString(5);
            }
        }

        static::updateFieldLayout($group->getFieldLayout());

        /** @var Command $command */
        $command = Craft::$app->controller;
        $message = sprintf('%s the `%s` tag group', $newGroup ? 'Creating' : 'Updating', $group->name);
        $command->do($message, function() use ($group) {
            if (!Craft::$app->tags->saveTagGroup($group)) {
                throw new Exception(implode(', ', $group->getFirstErrors()));
            }
        });

        return self::$tagGroups[static::uid()] = $group;
    }

    /**
     * Returns the tag group’s UUID.
     */
    abstract protected static function uid(): string;

    /**
     * Creates and configures the tag group (but doesn’t save it).
     */
    abstract protected static function populate(TagGroup $group): void;

    /**
     * Updates the tag group’s field layout.
     */
    protected static function updateFieldLayout(FieldLayout $fieldLayout): void
    {
    }
}
