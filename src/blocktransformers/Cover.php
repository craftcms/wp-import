<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\blocktransformers;

use craft\elements\Entry;
use craft\wpimport\BaseBlockTransformer;
use craft\wpimport\importers\Media;
use Symfony\Component\DomCrawler\Crawler;
use Throwable;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Cover extends BaseBlockTransformer
{
    public static function blockName(): string
    {
        return 'core/cover';
    }

    public function render(array $data, Entry $entry): string
    {
        if (!$entry->getFieldLayout()->getFieldByhandle('coverPhoto')) {
            return '';
        }

        if (isset($data['innerBlocks'][0]) && $data['innerBlocks'][0]['blockName'] === 'core/paragraph') {
            $nodes = (new Crawler($data['innerBlocks'][0]['innerHTML']))->filter('p');
            if ($nodes->count()) {
                $entry->setFieldValue('coverText', $nodes->html());
            }
        }

        $id = $data['attrs']['id'] ?? null;
        if ($id) {
            try {
                $assetId = $this->command->import(Media::SLUG, $id);
            } catch (Throwable) {
                $assetId = null;
            }
            if ($assetId) {
                $entry->setFieldValue('coverPhoto', [$assetId]);
            }
        }

        if (!empty($data['attrs']['overlayColor'])) {
            $entry->setFieldValue('coverOverlayColor', $this->command->normalizeColor($data['attrs']['overlayColor']));
        }

        return '';
    }
}
