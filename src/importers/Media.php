<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\importers;

use Craft;
use craft\base\ElementInterface;
use craft\elements\Asset;
use craft\enums\CmsEdition;
use craft\fs\Local;
use craft\helpers\Assets;
use craft\helpers\DateTimeHelper;
use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use craft\wpimport\BaseImporter;
use craft\wpimport\generators\fields\Caption;
use craft\wpimport\generators\fields\Description;
use craft\wpimport\generators\fields\WpTitle;
use craft\wpimport\generators\filesystems\Uploads as UploadsFs;
use craft\wpimport\generators\volumes\Uploads;
use craft\wpimport\generators\volumes\Uploads as UploadsVolume;
use Throwable;
use yii\console\Exception;

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

    public function populate(ElementInterface $element, array $data): void
    {
        $wpPath = $data['media_details']['file'] ?? pathinfo($data['source_url'], PATHINFO_BASENAME);
        $path = pathinfo($wpPath, PATHINFO_DIRNAME);
        $filename = Assets::prepareAssetName(pathinfo($wpPath, PATHINFO_BASENAME));
        $folder = Craft::$app->assets->ensureFolderByFullPathAndVolume($path !== '.' ? $path : '', UploadsVolume::get());

        /** @var Asset $element */
        $element->volumeId = Uploads::get()->id;
        $element->title = StringHelper::safeTruncate($data['title']['raw'], 255);
        $element->setFieldValue(WpTitle::get()->handle, $data['title']['raw']);

        /** @var Local $fs */
        $fs = UploadsFs::get();
        $destPath = sprintf('%s/%s/%s', $fs->getRootPath(), $path, $filename);

        if (file_exists($destPath)) {
            $element->folderId = $folder->id;
            $element->folderPath = $folder->path;
            $element->filename = $filename;
            $element->kind = Assets::getFileKindByExtension($filename);
            $element->setScenario(Asset::SCENARIO_INDEX);
        } else {
            $response = $this->command->client->get($data['source_url']);
            if ($response->getStatusCode() !== 200) {
                throw new Exception("No file found at {$data['source_url']}");
            }
            $tempPath = sprintf('%s/%s', Craft::$app->path->getTempAssetUploadsPath(), $filename);
            FileHelper::writeToFile($tempPath, $response->getBody()->getContents());
            $element->tempFilePath = $tempPath;
            $element->newFolderId = $folder->id;
            $element->newFilename = $filename;
            $element->avoidFilenameConflicts = true;
        }

        $element->setWidth($data['media_details']['width'] ?? null);
        $element->setHeight($data['media_details']['height'] ?? null);
        $element->size = $data['media_details']['filesize'] ?? null;
        $element->dateCreated = DateTimeHelper::toDateTime($data['date_gmt']);
        $element->dateUpdated = DateTimeHelper::toDateTime($data['modified_gmt']);
        if ($data['author'] && Craft::$app->edition->value >= CmsEdition::Pro->value) {
            try {
                $element->uploaderId = $this->command->import(User::SLUG, $data['author'], [
                    'roles' => User::ALL_ROLES,
                ]);
            } catch (Throwable) {
            }
        }
        $element->alt = $data['alt_text'];
        $element->setFieldValues([
            Caption::get()->handle => $data['caption']['raw'],
            Description::get()->handle => $data['description']['raw'],
        ]);
    }
}
