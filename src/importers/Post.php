<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\importers;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\elements\Entry;
use craft\elements\User as UserElement;
use craft\enums\CmsEdition;
use craft\helpers\DateTimeHelper;
use craft\wpimport\BaseImporter;
use yii\console\Exception;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Post extends BaseImporter
{
    public static function resource(): string
    {
        return 'posts';
    }

    public static function queryParams(): array
    {
        return [
            'status' => 'publish,future,draft,pending,private',
        ];
    }

    public static function elementType(): string
    {
        return Entry::class;
    }

    public function populate(ElementInterface $element, array $data): void
    {
        /** @var Entry $element */
        $element->sectionId = $this->command->postsSection->id;
        $element->setTypeId($this->command->postEntryType->id);

        if (Craft::$app->edition === CmsEdition::Solo) {
            $element->setAuthorId(UserElement::find()->admin()->limit(1)->ids()[0]);
        } else {
            $element->setAuthorId($this->command->import(User::resource(), $data['author']));
        }

        $element->title = $data['title']['raw'] ?: null;
        $element->slug = $data['slug'];
        $element->postDate = DateTimeHelper::toDateTime($data['date_gmt']);
        $element->dateUpdated = DateTimeHelper::toDateTime($data['modified_gmt']);
        $element->enabled = in_array($data['status'], ['publish', 'future']);

        if ($data['featured_media']) {
            $element->setFieldValue('featuredImage', $this->command->import(Media::resource(), $data['featured_media']));
        }

        $element->setFieldValues([
            'excerpt' => $data['excerpt']['raw'],
            $this->command->formatField->handle => $data['format'],
            $this->command->stickyField->handle => $data['sticky'],
            $this->command->categoriesField->handle => array_map(fn(int $id) => $this->command->import(Category::resource(), $id), $data['categories']),
            $this->command->tagsField->handle => array_map(fn(int $id) => $this->command->import(Tag::resource(), $id), $data['tags']),
        ]);

        if ($this->command->importComments) {
            $element->setFieldValue($this->command->commentsField->handle, [
                'commentEnabled' => $data['comment_status'] === 'open',
            ]);
        }

        if (!$element->id) {
            $element->setScenario(Element::SCENARIO_ESSENTIALS);
            if (!Craft::$app->elements->saveElement($element)) {
                throw new Exception(implode(', ', $element->getFirstErrors()));
            }
        }

        // render the blocks afterward, in case we need the ID
        $element->setFieldValue($this->command->postContentField->handle, $this->command->renderBlocks($data['content_parsed'], $element));
    }
}
