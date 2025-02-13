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
use craft\enums\Color;
use craft\fieldlayoutelements\CustomField;
use craft\fieldlayoutelements\entries\EntryTitleField;
use craft\helpers\DateTimeHelper;
use craft\helpers\Inflector;
use craft\helpers\StringHelper;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use craft\wpimport\BaseConfigurableImporter;
use craft\wpimport\Command;
use craft\wpimport\generators\fields\Caption;
use craft\wpimport\generators\fields\Color as ColorField;
use craft\wpimport\generators\fields\Comments;
use craft\wpimport\generators\fields\Description;
use craft\wpimport\generators\fields\Format;
use craft\wpimport\generators\fields\Media as MediaField;
use craft\wpimport\generators\fields\PostContent;
use craft\wpimport\generators\fields\Sticky;
use craft\wpimport\generators\fields\Tags;
use craft\wpimport\generators\fields\Template;
use craft\wpimport\generators\fields\WpId;
use craft\wpimport\generators\fields\WpTitle;
use Illuminate\Support\Collection;
use Throwable;
use yii\console\Exception;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class PostType extends BaseConfigurableImporter
{
    private EntryType $entryType;
    private Section $section;

    public function __construct(private array $data, Command $command, array $config = [])
    {
        parent::__construct($command, $config);
    }

    public function slug(): string
    {
        return $this->data['slug'];
    }

    public function apiUri(): string
    {
        return sprintf('%s/%s', $this->data['rest_namespace'], $this->data['rest_base'] ?: $this->data['name']);
    }

    public function label(): string
    {
        return Inflector::pluralize(StringHelper::titleize(str_replace('_', ' ', $this->data['labels']['name'])));
    }

    public function typeLabel(): string
    {
        return 'Post Type';
    }

    public function queryParams(): array
    {
        $params = [
            'status' => 'publish,future,draft,pending,private',
        ];
        if ($this->hierarchical()) {
            $params['orderby'] = 'menu_order';
            $params['order'] = 'asc';
        }
        return $params;
    }

    public function elementType(): string
    {
        return Entry::class;
    }

    public function populate(ElementInterface $element, array $data): void
    {
        /** @var Entry $element */
        $element->sectionId = $this->section()->id;
        $element->setTypeId($this->entryType()->id);

        if ($this->section()->type === Section::TYPE_STRUCTURE && $data['parent']) {
            $element->setParentId($this->command->import($this->slug(), $data['parent']));
        }

        if (Craft::$app->edition === CmsEdition::Solo) {
            $element->setAuthorId(UserElement::find()->admin()->limit(1)->ids()[0]);
        } elseif (!empty($data['author'])) {
            try {
                $element->setAuthorId($this->command->import(User::SLUG, $data['author'], [
                    'roles' => User::ALL_ROLES,
                ]));
            } catch (Throwable) {}
        }

        $title = $data['title']['raw'] ?? null;
        $element->title = $title !== null ? StringHelper::safeTruncate($title, 255) : null;
        $element->setFieldValue(WpTitle::get()->handle, $title);
        $element->slug = $data['slug'];
        $element->postDate = DateTimeHelper::toDateTime($data['date_gmt']);
        $element->dateUpdated = DateTimeHelper::toDateTime($data['modified_gmt']);
        $element->enabled = in_array($data['status'], ['publish', 'future']);

        $fieldValues = [
            Template::get()->handle => StringHelper::removeRight($data['template'] ?? '', '.php'),
        ];
        if ($this->supports('excerpt')) {
            $fieldValues['excerpt'] = $data['excerpt']['raw'] ?? null;
        }
        if ($this->supports('post-formats')) {
            $fieldValues[Format::get()->handle] = $data['format'] ?? null;
        }
        if (!$this->hierarchical()) {
            $fieldValues[Sticky::get()->handle] = $data['sticky'] ?? false;
        }
        if ($this->hasTaxonomy('post_tag')) {
            $fieldValues[Tags::get()->handle] = Collection::make($data['tags'])
                ->map(function(int $id) {
                    try {
                        return $this->command->import(Tag::SLUG, $id);
                    } catch (Throwable) {
                        return null;
                    }
                })
                ->filter()
                ->all();
        }
        if ($data['featured_media'] ?? null) {
            try {
                $fieldValues['featuredImage'] = [$this->command->import(Media::SLUG, $data['featured_media'])];
            } catch (Throwable) {}
        }
        if ($this->supports('comments') && $this->command->importComments) {
            $fieldValues[Comments::get()->handle] = [
                'commentEnabled' => ($data['comment_status'] ?? null) === 'open',
            ];
        }

        foreach ($this->data['taxonomies'] as $taxonomy) {
            if ($taxonomy === 'post_tag') {
                continue;
            }

            /** @var Taxonomy $importer */
            $importer = $this->command->importers[$taxonomy];
            $fieldValues[$importer->field()->handle] = Collection::make(match ($taxonomy) {
                'category' => $data['categories'],
                default => $data[$taxonomy],
            })
                ->map(function(int $id) use ($importer) {
                    try {
                        return $this->command->import($importer->slug(), $id);
                    } catch (Throwable) {
                        return null;
                    }
                })
                ->filter()
                ->all();
        }

        if (!empty($data['acf'])) {
            $fieldValues = array_merge($fieldValues, $this->command->prepareAcfFieldValues(
                $this->command->fieldsForEntity('post_type', $this->slug()),
                $data['acf'],
            ));
        }

        foreach ($fieldValues as $handle => $value) {
            try {
                $element->setFieldValue($handle, $value);
            } catch (Throwable) {
            }
        }

        if (!empty($data['content_parsed'])) {
            // save the entry first, so it gets an ID
            if (!$element->id) {
                $element->setScenario(Element::SCENARIO_ESSENTIALS);
                if (!Craft::$app->elements->saveElement($element)) {
                    throw new Exception(implode(', ', $element->getFirstErrors()));
                }
            }
            $element->setFieldValue(PostContent::get()->handle, $this->command->renderBlocks($data['content_parsed'], $element));
        }
    }

    public function entryType(): EntryType
    {
        if (isset($this->entryType)) {
            return $this->entryType;
        }

        $entryTypeHandle = StringHelper::toHandle($this->data['labels']['singular_name']);
        $entryType = Craft::$app->entries->getEntryTypeByHandle($entryTypeHandle);
        $newEntryType = !$entryType;

        if ($newEntryType) {
            $entryType = new EntryType();
            $entryType->name = $this->data['labels']['singular_name'];
            $entryType->handle = $entryTypeHandle;
            $entryType->icon = $this->command->normalizeIcon($this->data['icon'] ?? null) ?? 'pen-nib';
            $entryType->color = Color::Blue;
        }

        $contentElements = [];
        if ($this->supports('title')) {
            $contentElements[] = new EntryTitleField([
                'required' => false,
            ]);
        }
        $contentElements[] = new CustomField(PostContent::get());

        $metaElements = [];
        if ($this->supports('thumbnail')) {
            $metaElements[] = new CustomField(MediaField::get(), [
                'label' => 'Featured Image',
                'handle' => 'featuredImage',
            ]);
        }
        if ($this->supports('excerpt')) {
            $metaElements[] = new CustomField(Caption::get(), [
                'label' => 'Excerpt',
                'handle' => 'excerpt',
            ]);
        }
        if ($this->supports('comments') && $this->command->importComments) {
            $metaElements[] = new CustomField(Comments::get());
        }
        if ($this->supports('post-formats')) {
            $metaElements[] = new CustomField(Format::get());
        }
        if (!$this->hierarchical()) {
            $metaElements[] = new CustomField(Sticky::get());
        }

        foreach ($this->data['taxonomies'] as $taxonomy) {
            if ($taxonomy === 'post_tag') {
                continue;
            }

            /** @var Taxonomy $importer */
            $importer = $this->command->importers[$taxonomy];
            $metaElements[] = new CustomField($importer->field());
        }

        if ($this->hasTaxonomy('post_tag')) {
            $metaElements[] = new CustomField(Tags::get());
        }

        $metaElements[] = new CustomField(WpId::get());
        $metaElements[] = new CustomField(WpTitle::get());
        $metaElements[] = new CustomField(Template::get());

        $fieldLayout = $entryType->getFieldLayout();
        $this->command->addElementsToLayout($fieldLayout, 'Content', $contentElements, true, true);
        $this->command->addElementsToLayout($fieldLayout, 'Cover Photo', [
            new CustomField(Description::get(), [
                'label' => 'Cover Text',
                'handle' => 'coverText',
            ]),
            new CustomField(MediaField::get(), [
                'label' => 'Cover Photo',
                'handle' => 'coverPhoto',
            ]),
            new CustomField(ColorField::get(), [
                'label' => 'Cover Overlay Color',
                'handle' => 'coverOverlayColor',
            ]),
        ]);
        $this->command->addAcfFieldsToLayout('post_type', $this->slug(), $fieldLayout);
        $this->command->addElementsToLayout($fieldLayout, 'Meta', $metaElements);

        $message = sprintf('%s the `%s` entry type', $newEntryType ? 'Creating' : 'Updating', $entryType->name);
        $this->command->do($message, function() use ($entryType) {
            if (!Craft::$app->entries->saveEntryType($entryType)) {
                throw new Exception(implode(', ', $entryType->getFirstErrors()));
            }
        });

        return $this->entryType = $entryType;
    }

    public function section(): Section
    {
        if (isset($this->section)) {
            return $this->section;
        }

        $sectionHandle = StringHelper::toHandle($this->label());
        $section = Craft::$app->entries->getSectionByHandle($sectionHandle);
        if ($section) {
            return $this->section = $section;
        }

        $section = new Section();
        $section->name = $this->label();
        $section->handle = $sectionHandle;
        $section->type = $this->hierarchical() ? Section::TYPE_STRUCTURE : Section::TYPE_CHANNEL;
        $section->enableVersioning = $this->supports('revisions');
        $section->setEntryTypes([$this->entryType()]);
        $section->setSiteSettings([
            new Section_SiteSettings([
                'siteId' => Craft::$app->sites->getPrimarySite()->id,
                'hasUrls' => true,
                'uriFormat' => strtr(trim($this->command->wpInfo['permalink_structure'], '/'), [
                    '%year%' => "{postDate|date('Y')}",
                    '%monthnum%' => "{postDate|date('m')}",
                    '%day%' => "{postDate|date('d')}",
                    '%hour%' => "{postDate|date('H')}",
                    '%minute%' => "{postDate|date('i')}",
                    '%second%' => "{postDate|date('s')}",
                    '%post_id%' => '{id}',
                    '%postname%' => '{slug}',
                    '%category%' => "{categories.one().slug ?? 'uncategorized'}",
                    '%author%' => '{author.username}',
                ]),
                'template' => '_post.twig',
            ]),
        ]);
        $section->previewTargets = [
            [
                'label' => Craft::t('app', 'Primary {type} page', [
                    'type' => Entry::lowerDisplayName(),
                ]),
                'urlFormat' => '{url}',
            ],
        ];

        $this->command->do("Creating `$section->name` section", function() use ($section) {
            if (!Craft::$app->entries->saveSection($section)) {
                throw new Exception(implode(', ', $section->getFirstErrors()));
            }
        });

        return $this->section = $section;
    }

    private function hierarchical(): bool
    {
        return $this->data['hierarchical'] ?? false;
    }

    private function supports(string $feature): bool
    {
        return $this->data['supports'][$feature] ?? false;
    }

    private function hasTaxonomy(string $name): bool
    {
        return in_array($name, $this->data['taxonomies']);
    }
}
