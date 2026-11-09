<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\Importers;

use CraftCms\Cms\Asset\AssetsHelper;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Validation\AssetRules;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Facades\Folders;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Str;
use CraftCms\WpImport\Generators\Fields\Caption;
use CraftCms\WpImport\Generators\Fields\Description;
use CraftCms\WpImport\Generators\Fields\WpTitle;
use CraftCms\WpImport\Generators\Volumes\WpContent;
use RuntimeException;
use Throwable;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Media extends BaseImporter
{
    public const SLUG = 'media';

    public function slug(): string
    {
        return self::SLUG;
    }

    public function apiUri(): string
    {
        return 'wp/v2/media';
    }

    public function label(): string
    {
        return 'Media';
    }

    public function elementType(): string
    {
        return Asset::class;
    }

    public function find(array $data): ?ElementInterface
    {
        [, $folder, $filename] = $this->fileInfo($data);

        return Asset::find()->folderId($folder->id)->filename($filename)->one();
    }

    public function populate(ElementInterface $element, array $data): void
    {
        /** @var Asset $element */
        [$volume, $folder, $filename] = $this->fileInfo($data);
        $title = $data['title']['raw'] ?? $filename;
        /** @var Asset $element */
        $element->title = Str::limit($title, 255, '', true);
        $element->setFieldValue(WpTitle::get()->handle, $title);
        if (! empty($data['date_gmt'])) {
            $element->dateCreated = DateTimeHelper::toDateTime($data['date_gmt']);
        }
        if (! empty($data['modified_gmt'])) {
            $element->dateUpdated = DateTimeHelper::toDateTime($data['modified_gmt']);
        }
        if (! empty($data['author']) && Edition::isAtLeast(Edition::Pro)) {
            try {
                $element->uploaderId = $this->command->import(User::SLUG, $data['author'], [
                    'roles' => User::ALL_ROLES,
                ]);
            } catch (Throwable) {
            }
        }
        if (! empty($data['alt_text'])) {
            $element->alt = $data['alt_text'];
        }
        $element->setFieldValues(array_filter([
            Caption::get()->handle => $data['caption']['raw'] ?? null,
            Description::get()->handle => $data['description']['raw'] ?? null,
        ]));

        // only update the file if it's a new asset
        if (! $element->id) {
            $element->setVolumeId($volume->id);

            $filePath = sprintf('%s%s', $folder->path ? "$folder->path/" : '', $filename);
            if ($volume->sourceDisk()->exists($filePath)) {
                $element->folderId = $folder->id;
                $element->folderPath = $folder->path;
                $element->filename = $filename;
                $element->kind = AssetsHelper::getFileKindByExtension($filename);
                $element->ruleset->useScenario(AssetRules::SCENARIO_INDEX);
            } else {
                $response = $this->command->client->get($data['source_url']);
                if ($response->getStatusCode() !== 200) {
                    throw new RuntimeException("No file found at {$data['source_url']}");
                }
                $tempPath = sprintf('%s/%s', Path::tempAssetUploads(), $filename);
                File::writeToFile($tempPath, $response->getBody()->getContents());
                $element->tempFilePath = $tempPath;
                $element->newFolderId = $folder->id;
                $element->newFilename = $filename;
                $element->avoidFilenameConflicts = true;
            }
        }
    }

    /**
     * @return array{0:Volume, 1:VolumeFolder, 2:string}
     */
    private function fileInfo(array $data): array
    {
        $wpPath = $data['media_details']['file'] ?? pathinfo($data['source_url'], PATHINFO_BASENAME);
        $path = pathinfo($wpPath, PATHINFO_DIRNAME);
        $volume = WpContent::get();
        $folder = Folders::ensureFolderByFullPathAndVolume($path !== '.' ? $path : '', $volume);
        $filename = AssetsHelper::prepareAssetName(pathinfo($wpPath, PATHINFO_BASENAME));

        return [$volume, $folder, $filename];
    }
}
