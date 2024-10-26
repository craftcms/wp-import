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
use craft\helpers\StringHelper;
use craft\wpimport\BaseImporter;
use craft\wpimport\generators\entrytypes\Page as PageEntryType;
use craft\wpimport\generators\fields\Comments;
use craft\wpimport\generators\fields\PostContent;
use craft\wpimport\generators\fields\Template;
use craft\wpimport\generators\sections\Pages;
use yii\console\Exception;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Page extends BaseImporter
{
    public const RESOURCE = 'pages';

    public function resource(): string
    {
        return self::RESOURCE;
    }

    public function label(): string
    {
        return 'Pages';
    }

    public function queryParams(): array
    {
        return [
            'status' => 'publish,future,draft,pending,private',
            'orderby' => 'menu_order',
            'order' => 'asc',
        ];
    }

    public function elementType(): string
    {
        return Entry::class;
    }

    public function populate(ElementInterface $element, array $data): void
    {
        /** @var Entry $element */
        $element->sectionId = Pages::get()->id;
        $element->setTypeId(PageEntryType::get()->id);

        if (Craft::$app->edition === CmsEdition::Solo) {
            $element->setAuthorId(UserElement::find()->admin()->limit(1)->ids()[0]);
        } else {
            $element->setAuthorId($this->command->import(User::RESOURCE, $data['author']));
        }

        if ($data['parent']) {
            $element->setParentId($this->command->import(static::RESOURCE, $data['parent']));
        }

        $element->title = $data['title']['raw'] ?: null;
        $element->slug = $data['slug'];
        $element->postDate = DateTimeHelper::toDateTime($data['date_gmt']);
        $element->dateUpdated = DateTimeHelper::toDateTime($data['modified_gmt']);
        $element->enabled = in_array($data['status'], ['publish', 'future']);

        if ($data['featured_media']) {
            $element->setFieldValue('featuredImage', $this->command->import(Media::RESOURCE, $data['featured_media']));
        }

        if ($this->command->importComments) {
            $element->setFieldValue(Comments::get()->handle, [
                'commentEnabled' => $data['comment_status'] === 'open',
            ]);
        }

        $element->setFieldValue(Template::get()->handle, StringHelper::removeRight($data['template'] ?? '', '.php'));

        if (!$element->id) {
            $element->setScenario(Element::SCENARIO_ESSENTIALS);
            if (!Craft::$app->elements->saveElement($element)) {
                throw new Exception(implode(', ', $element->getFirstErrors()));
            }
        }

        // render the blocks afterward, in case we need the ID
        $element->setFieldValue(PostContent::get()->handle, $this->command->renderBlocks($data['content_parsed'], $element));
    }
}
