<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\AcfAdapters;

use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Str;
use RuntimeException;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class FlexibleContent extends BaseAcfAdapter
{
    public static function type(): string
    {
        return 'flexible_content';
    }

    public function create(array $data): FieldInterface
    {
        $entryTypes = array_map(fn (array $layoutData) => $this->entryType($data, $layoutData), $data['layouts']);
        $field = new Matrix;
        $field->setEntryTypes($entryTypes);
        $field->viewMode = ($data['pagination'] ?? false) ? Matrix::VIEW_MODE_INDEX : Matrix::VIEW_MODE_BLOCKS;
        if ($data['min'] ?? false) {
            $field->minEntries = $data['min'];
        }
        if ($data['max'] ?? false) {
            $field->maxEntries = $data['max'];
        }
        if (($data['button_label'] ?? false) && $data['button_label'] !== 'Add Row') {
            $field->createButtonLabel = $data['button_label'];
        }

        return $field;
    }

    private function entryType(array $fieldData, array $layoutData): EntryType
    {
        $entryTypeHandle = sprintf(
            'acf_%s_%s_%s',
            Str::toHandle($fieldData['name']), $fieldData['ID'], $layoutData['name'],
        );
        $entryType = EntryTypes::getEntryTypeByHandle($entryTypeHandle);
        if ($entryType) {
            return $entryType;
        }

        $entryType = new EntryType;
        $entryType->name = $layoutData['label'] ?: "Untitled ACF Field {$layoutData['ID']}";
        $entryType->handle = $entryTypeHandle;

        $fieldLayout = new FieldLayout;
        $fieldLayout->setTabs([
            new FieldLayoutTab([
                'layout' => $fieldLayout,
                'name' => 'Content',
                'elements' => $this->command->acfFieldElements($layoutData['sub_fields']),
            ]),
        ]);
        $entryType->setFieldLayout($fieldLayout);

        $this->command->do("Creating `$entryType->name` entry type", function () use ($entryType) {
            if (! EntryTypes::saveEntryType($entryType)) {
                throw new RuntimeException(implode(', ', $entryType->getFirstErrors()));
            }
        });

        return $entryType;
    }

    public function normalizeValue(mixed $value, array $data): mixed
    {
        return array_map(function (array $row) use ($data) {
            $layoutName = Arr::pull($row, 'acf_fc_layout');
            $layoutData = Arr::first(
                $data['layouts'],
                fn (array $layoutData) => $layoutData['name'] === $layoutName,
            );
            $entryType = $this->entryType($data, $layoutData);

            return [
                'type' => $entryType->handle,
                'fields' => $this->command->prepareAcfFieldValues($layoutData['sub_fields'], $row),
            ];
        }, $value);
    }
}
