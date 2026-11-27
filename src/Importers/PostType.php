<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\Importers;

use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Element\Validation\ElementRules;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Data\SectionSiteSettings;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Shared\Enums\Color;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\WpImport\Console\ImportCommand;
use CraftCms\WpImport\Generators\Fields\Caption;
use CraftCms\WpImport\Generators\Fields\Color as ColorField;
use CraftCms\WpImport\Generators\Fields\Description;
use CraftCms\WpImport\Generators\Fields\Format;
use CraftCms\WpImport\Generators\Fields\Media as MediaField;
use CraftCms\WpImport\Generators\Fields\PostContent;
use CraftCms\WpImport\Generators\Fields\Sticky;
use CraftCms\WpImport\Generators\Fields\Tags;
use CraftCms\WpImport\Generators\Fields\Template;
use CraftCms\WpImport\Generators\Fields\WpId;
use CraftCms\WpImport\Generators\Fields\WpTitle;
use Illuminate\Support\Collection;
use RuntimeException;
use Throwable;

use function CraftCms\Cms\t;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class PostType extends BaseConfigurableImporter
{
    private EntryType $entryType;

    private Section $section;

    public function __construct(private array $data, ImportCommand $command)
    {
        parent::__construct($command);
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
        return Str::plural(Str::title(str_replace('_', ' ', $this->data['labels']['name'])));
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
        /** @var Entry $element */
        $element->sectionId = $this->section()->id;
        $element->setTypeId($this->entryType()->id);

        if ($this->section()->type === SectionType::Structure && $data['parent']) {
            $element->setParentId($this->command->import($this->slug(), $data['parent']));
        }

        if (Edition::get() === Edition::Solo) {
            $element->setAuthorId(UserElement::find()->admin()->limit(1)->ids()[0]);
        } elseif (! empty($data['author'])) {
            try {
                $element->setAuthorId($this->command->import(User::SLUG, $data['author'], [
                    'roles' => User::ALL_ROLES,
                ]));
            } catch (Throwable) {
            }
        }

        $title = $data['title']['raw'] ?? null;
        $element->title = $title !== null ? Str::limit($title, 255, '', true) : null;
        $element->setFieldValue(WpTitle::get()->handle, $title);
        $element->slug = $data['slug'];
        $element->postDate = DateTimeHelper::toDateTime($data['date_gmt']);
        $element->dateUpdated = DateTimeHelper::toDateTime($data['modified_gmt']);
        $element->enabled = in_array($data['status'], ['publish', 'future']);

        $fieldValues = [
            Template::get()->handle => Str::chopEnd($data['template'] ?? '', '.php'),
        ];
        if ($this->supports('excerpt')) {
            $fieldValues['excerpt'] = $data['excerpt']['raw'] ?? null;
        }
        if ($this->supports('post-formats')) {
            $fieldValues[Format::get()->handle] = $data['format'] ?? null;
        }
        if (! $this->hierarchical()) {
            $fieldValues[Sticky::get()->handle] = $data['sticky'] ?? false;
        }
        if ($this->hasTaxonomy('post_tag')) {
            $fieldValues[Tags::get()->handle] = Collection::make($data['tags'])
                ->map(function (int $id) {
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
            } catch (Throwable) {
            }
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
                ->map(function (int $id) use ($importer) {
                    try {
                        return $this->command->import($importer->slug(), $id);
                    } catch (Throwable) {
                        return null;
                    }
                })
                ->filter()
                ->all();
        }

        if (! empty($data['acf'])) {
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

        if (! empty($data['content_parsed'])) {
            // save the entry first, so it gets an ID
            if (! $element->id) {
                $element->ruleset->useScenario(ElementRules::SCENARIO_ESSENTIALS);
                if (! Elements::saveElement($element)) {
                    throw new RuntimeException(implode(', ', $element->getFirstErrors()));
                }
            }
            $element->setFieldValue(PostContent::get()->handle, $this->command->renderBlocks($data['content_parsed'], $element));
        }
    }

    public function existingElementQuery(): ElementQueryInterface
    {
        return Entry::find()->sectionId($this->section()->id);
    }

    public function entryType(): EntryType
    {
        if (isset($this->entryType)) {
            return $this->entryType;
        }

        $entryTypeHandle = Str::toHandle($this->data['labels']['singular_name']);
        $entryType = EntryTypes::getEntryTypeByHandle($entryTypeHandle);
        $newEntryType = ! $entryType;

        if ($newEntryType) {
            $entryType = new EntryType;
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
        if ($this->supports('post-formats')) {
            $metaElements[] = new CustomField(Format::get());
        }
        if (! $this->hierarchical()) {
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
        $this->command->do($message, function () use ($entryType) {
            if (! EntryTypes::saveEntryType($entryType)) {
                throw new RuntimeException(implode(', ', $entryType->getFirstErrors()));
            }
        });

        return $this->entryType = $entryType;
    }

    public function section(): Section
    {
        if (isset($this->section)) {
            return $this->section;
        }

        $sectionHandle = Str::toHandle($this->label());
        $section = Sections::getSectionByHandle($sectionHandle);
        if ($section) {
            return $this->section = $section;
        }

        $section = new Section;
        $section->name = $this->label();
        $section->handle = $sectionHandle;
        $section->type = $this->hierarchical() ? SectionType::Structure : SectionType::Channel;
        $section->enableVersioning = $this->supports('revisions');
        $section->setEntryTypes([$this->entryType()]);
        $section->setSiteSettings([
            new SectionSiteSettings([
                'siteId' => Sites::getPrimarySite()->id,
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
                'label' => t('Primary {type} page', [
                    'type' => Entry::lowerDisplayName(),
                ]),
                'urlFormat' => '{url}',
            ],
        ];

        $this->command->do("Creating `$section->name` section", function () use ($section) {
            if (! Sections::saveSection($section)) {
                throw new RuntimeException(implode(', ', $section->getFirstErrors()));
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
