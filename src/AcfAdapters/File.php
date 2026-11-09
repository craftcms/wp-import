<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\AcfAdapters;

use CraftCms\Cms\Asset\AssetsHelper;
use CraftCms\Cms\Field\Assets;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\WpImport\Generators\Volumes\WpContent;
use CraftCms\WpImport\Importers\Media;
use Illuminate\Support\Collection;
use Symfony\Component\Mime\MimeTypes;
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
        $field = new Assets;
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
                    array_push($allowedExtensions, ...MimeTypes::getDefault()->getExtensions($type));
                } else {
                    $allowedExtensions[] = $type;
                }
            }

            $allowedKinds = [];
            $fileKinds = AssetsHelper::getFileKinds();

            foreach ($allowedExtensions as $extension) {
                foreach ($fileKinds as $kind => $kindInfo) {
                    if (in_array($extension, $kindInfo['extensions'])) {
                        $allowedKinds[] = $kind;
                        break;
                    }
                }
            }

            $this->restrictFileKinds($field, array_values(array_unique($allowedKinds)));
        }

        return $field;
    }

    public function normalizeValue(mixed $value, array $data): mixed
    {
        return Collection::make((array) $value)
            ->map(function (int $id) {
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
