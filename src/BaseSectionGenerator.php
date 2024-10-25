<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport;

use Craft;
use craft\helpers\StringHelper;
use craft\models\Section;
use yii\console\Exception;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
abstract class BaseSectionGenerator
{
    public static function get(): Section
    {
        $section = Craft::$app->entries->getSectionByUid(static::uid());

        if (!$section) {
            $section = new Section();
            $section->uid = static::uid();
            static::populate($section);

            if (Craft::$app->entries->getSectionByHandle($section->handle) !== null) {
                $section->handle .= '_' . StringHelper::randomString(5);
            }

            /** @var Command $command */
            $command = Craft::$app->controller;
            $command->do("Creating `$section->name` section", function() use ($section) {
                if (!Craft::$app->entries->saveSection($section)) {
                    throw new Exception(implode(', ', $section->getFirstErrors()));
                }
            });
        }

        return $section;
    }

    /**
     * Returns the section’s UUID.
     */
    abstract protected static function uid(): string;

    /**
     * Creates and configures the section (but doesn’t save it).
     */
    abstract protected static function populate(Section $section): void;
}
