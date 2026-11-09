<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\acfadapters;

use craft\base\FieldInterface;
use craft\fields\Assets;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\FileHelper;
use craft\wpimport\BaseAcfAdapter;
use craft\wpimport\generators\volumes\WpContent;
use craft\wpimport\importers\Media;
use Illuminate\Support\Collection;
use Throwable;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class File extends BaseAcfAdapter
{
    public static function type(): string
    {
        return 'file';
    }

    public function create(array $data): FieldInterface
    {
        $sourceKey = sprintf('volume:%s', WpContent::get()->uid);
        $field = new Assets();
        $field->maxRelations = 1;
        $field->sources = [$sourceKey];
        $field->viewMode = 'large';
        $field->defaultUploadLocationSource = $sourceKey;

        if ($data['mime_types']) {
            // Get all the allowed file extensions
            $allowedExtensions = [];
            $types = array_map('trim', explode(',', $data['mime_types']));
            foreach ($types as $type) {
                if (str_contains($type, '/')) {
                    array_push($allowedExtensions, ...FileHelper::getExtensionsByMimeType($type));
                } else {
                    $allowedExtensions[] = $type;
                }
            }

            $field->restrictFiles = true;
            $field->allowedKinds = [];
            $fileKinds = AssetsHelper::getFileKinds();

            foreach ($allowedExtensions as $extension) {
                foreach ($fileKinds as $kind => $kindInfo) {
                    if (in_array($extension, $kindInfo['extensions'])) {
                        $field->allowedKinds[] = $kind;
                        break;
                    }
                }
            }
        }

        return $field;
    }

    public function normalizeValue(mixed $value, array $data): mixed
    {
        return Collection::make((array)$value)
            ->map(function(int $id) {
                try {
                    return $this->command->import(Media::SLUG, $id);
                } catch (Throwable) {
                    return null;
                }
            })
            ->filter()
            ->all();
    }
}
