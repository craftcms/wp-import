<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\acfadapters;

use craft\base\FieldInterface;
use craft\fields\Assets;
use craft\wpimport\BaseAcfAdapter;
use craft\wpimport\generators\volumes\WpContent;
use craft\wpimport\importers\Media;
use Illuminate\Support\Collection;
use Throwable;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Image extends BaseAcfAdapter
{
    public static function type(): string
    {
        return 'image';
    }

    public function create(array $data): FieldInterface
    {
        $sourceKey = sprintf('volume:%s', WpContent::get()->uid);
        $field = new Assets();
        $field->maxRelations = 1;
        $field->sources = [$sourceKey];
        $field->viewMode = 'large';
        $field->defaultUploadLocationSource = $sourceKey;
        $field->restrictFiles = true;
        $field->allowedKinds = ['image'];
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
