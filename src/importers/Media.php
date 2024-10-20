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
use craft\helpers\Assets;
use craft\helpers\DateTimeHelper;
use craft\helpers\FileHelper;
use craft\wpimport\BaseImporter;
use yii\console\Exception;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Media extends BaseImporter
{
    public static function resource(): string
    {
        return 'media';
    }

    public static function elementType(): string
    {
        return Asset::class;
    }

    public function populate(ElementInterface $element, array $data): void
    {
        $wpPath = $data['media_details']['file'] ?? pathinfo($data['source_url'], PATHINFO_BASENAME);
        $path = pathinfo($wpPath, PATHINFO_DIRNAME);
        $filename = Assets::prepareAssetName(pathinfo($wpPath, PATHINFO_BASENAME));
        $folder = Craft::$app->assets->ensureFolderByFullPathAndVolume($path !== '.' ? $path : '', $this->command->mediaVolume);

        /** @var Asset $element */
        $element->volumeId = $this->command->mediaVolume->id;
        $element->title = $data['title']['raw'];

        $destPath = sprintf('%s/%s/%s', $this->command->mediaFs->getRootPath(), $path, $filename);
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
        if (isset($data['author'], $this->command->userIds[$data['author']])) {
            $element->uploaderId = $this->command->userIds[$data['author']];
        }
        $element->alt = $data['alt_text'];
        $element->setFieldValues([
            $this->command->captionField->handle => $data['caption']['raw'],
            $this->command->descriptionField->handle => $data['description']['raw'],
        ]);
    }
}
