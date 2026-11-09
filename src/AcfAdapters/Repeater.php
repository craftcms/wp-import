<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\acfadapters;

use Craft;
use craft\base\FieldInterface;
use craft\fields\Matrix;
use craft\helpers\StringHelper;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\wpimport\BaseAcfAdapter;
use yii\console\Exception;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Repeater extends BaseAcfAdapter
{
    public static function type(): string
    {
        return 'repeater';
    }

    public function create(array $data): FieldInterface
    {
        $entryType = $this->entryType($data);
        $field = new Matrix();
        $field->setEntryTypes([$entryType]);
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

    private function entryType(array $data): EntryType
    {
        $entryTypeHandle = sprintf('acf_%s_%s', StringHelper::toHandle($data['name']), $data['ID']);
        $entryType = Craft::$app->entries->getEntryTypeByHandle($entryTypeHandle);
        if ($entryType) {
            return $entryType;
        }

        $entryType = new EntryType();
        $entryType->name = $data['label'] ?: "Untitled ACF Field {$data['ID']}";
        $entryType->handle = $entryTypeHandle;

        $fieldLayout = new FieldLayout();
        $fieldLayout->setTabs([
            new FieldLayoutTab([
                'layout' => $fieldLayout,
                'name' => 'Content',
                'elements' => $this->command->acfFieldElements($data['sub_fields']),
            ]),
        ]);
        $entryType->setFieldLayout($fieldLayout);

        $this->command->do("Creating `$entryType->name` entry type", function() use ($entryType) {
            if (!Craft::$app->entries->saveEntryType($entryType)) {
                throw new Exception(implode(', ', $entryType->getFirstErrors()));
            }
        });

        return $entryType;
    }

    public function normalizeValue(mixed $value, array $data): mixed
    {
        $entryType = $this->entryType($data);
        return array_map(fn(array $row) => [
            'type' => $entryType->handle,
            'fields' => $this->command->prepareAcfFieldValues($data['sub_fields'], $row),
        ], $value);
    }
}
