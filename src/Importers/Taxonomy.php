<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\Importers;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Data\SectionSiteSettings;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Shared\Enums\Color;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use CraftCms\WpImport\Console\ImportCommand;
use CraftCms\WpImport\Generators\Fields\Description;
use CraftCms\WpImport\Generators\Fields\WpId;
use CraftCms\WpImport\Generators\Fields\WpTitle;
use RuntimeException;
use Throwable;

/**
 * Imports a WordPress taxonomy’s terms as entries.
 *
 * Craft 6 has no categories, so hierarchical taxonomies get a Structure section and
 * flat ones get a Channel section. Posts relate to them with an Entries field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Taxonomy extends BaseConfigurableImporter
{
    private EntryType $entryType;

    private Section $section;

    private Entries $field;

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
        return sprintf('%s/%s', $this->data['rest_namespace'], $this->data['rest_base'] ?: $this->data['slug']);
    }

    public function label(): string
    {
        return $this->data['name'];
    }

    public function typeLabel(): string
    {
        return 'Taxonomy';
    }

    public function elementType(): string
    {
        return Entry::class;
    }

    public function existingElementQuery(): ElementQueryInterface
    {
        return Entry::find()->sectionId($this->section()->id);
    }

    public function populate(ElementInterface $element, array $data): void
    {
        /** @var Entry $element */
        $element->sectionId = $this->section()->id;
        $element->setTypeId($this->entryType()->id);

        if ($this->hierarchical() && ! empty($data['parent'])) {
            $element->setParentId($this->command->import($this->slug(), $data['parent']));
        }

        $element->title = Str::limit($data['name'], 255, '', true);
        $element->setFieldValue(WpTitle::get()->handle, $data['name']);
        $element->slug = $data['slug'];

        $fieldValues = [
            Description::get()->handle => $data['description'] ?? null,
        ];

        if (! empty($data['acf'])) {
            $fieldValues = array_merge($fieldValues, $this->command->prepareAcfFieldValues(
                $this->command->fieldsForEntity('taxonomy', $this->slug()),
                $data['acf'],
            ));
        }

        foreach ($fieldValues as $handle => $value) {
            try {
                $element->setFieldValue($handle, $value);
            } catch (Throwable) {
            }
        }
    }

    public function entryType(): EntryType
    {
        if (isset($this->entryType)) {
            return $this->entryType;
        }

        $name = $this->data['labels']['singular_name'] ?? Str::singular($this->label());
        $handle = Str::toHandle($name);
        $entryType = EntryTypes::getEntryTypeByHandle($handle);
        $newEntryType = ! $entryType;

        if ($newEntryType) {
            $entryType = new EntryType;
            $entryType->name = $name;
            $entryType->handle = $handle;
            $entryType->icon = $this->hierarchical() ? 'folder' : 'tag';
            $entryType->color = Color::Amber;
        }

        $fieldLayout = $entryType->getFieldLayout();
        $this->command->addElementsToLayout($fieldLayout, 'Content', [
            new EntryTitleField,
            new CustomField(Description::get()),
        ], true, true);
        $this->command->addAcfFieldsToLayout('taxonomy', $this->slug(), $fieldLayout);
        $this->command->addElementsToLayout($fieldLayout, 'Meta', [
            new CustomField(WpId::get()),
            new CustomField(WpTitle::get()),
        ]);

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

        $handle = Str::toHandle($this->label());
        $section = Sections::getSectionByHandle($handle);
        if ($section) {
            return $this->section = $section;
        }

        $section = new Section;
        $section->name = $this->label();
        $section->handle = $handle;
        $section->type = $this->hierarchical() ? SectionType::Structure : SectionType::Channel;
        $section->enableVersioning = false;
        $section->setEntryTypes([$this->entryType()]);
        $section->setSiteSettings(array_map(fn (Site $site) => new SectionSiteSettings([
            'siteId' => $site->id,
        ]), Sites::getAllSites(true)->all()));

        $this->command->do("Creating `$section->name` section", function () use ($section) {
            if (! Sections::saveSection($section)) {
                throw new RuntimeException(implode(', ', $section->getFirstErrors()));
            }
        });

        return $this->section = $section;
    }

    public function field(): Entries
    {
        if (isset($this->field)) {
            return $this->field;
        }

        $handle = Str::toHandle($this->label());
        $field = Fields::getFieldByHandle($handle);

        if ($field instanceof Entries) {
            return $this->field = $field;
        }

        if ($field) {
            $handle .= '_'.Str::random(5);
        }

        $field = new Entries;
        $field->name = $this->label();
        $field->handle = $handle;
        $field->sources = [sprintf('section:%s', $this->section()->uid)];

        $this->command->do("Creating `$field->name` Entries field", function () use ($field) {
            if (! Fields::saveField($field)) {
                throw new RuntimeException(implode(', ', $field->getFirstErrors()));
            }
        });

        return $this->field = $field;
    }

    private function hierarchical(): bool
    {
        return $this->data['hierarchical'] ?? false;
    }
}
