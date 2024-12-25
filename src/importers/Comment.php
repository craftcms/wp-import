<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\wpimport\importers;

use Craft;
use craft\base\ElementInterface;
use craft\elements\User as UserElement;
use craft\enums\CmsEdition;
use craft\fieldlayoutelements\CustomField;
use craft\helpers\DateTimeHelper;
use craft\wpimport\BaseImporter;
use craft\wpimport\generators\fields\WpId;
use Throwable;
use verbb\comments\elements\Comment as CommentElement;
use verbb\comments\services\Comments as CommentsService;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Comment extends BaseImporter
{
    public const SLUG = 'comment';

    public function slug(): string
    {
        return self::SLUG;
    }

    public function apiUri(): string
    {
        return 'wp/v2/comments';
    }

    public function label(): string
    {
        return 'Comments';
    }

    public function elementType(): string
    {
        return CommentElement::class;
    }

    public function supported(?string &$reason): bool
    {
        if (!$this->command->importComments) {
            $reason = 'The Comments plugin isn’t enabled.';
            return false;
        }
        return true;
    }

    public function prep(): void
    {
        $this->command->do('Updating the comment field layout', function() {
            $fieldLayout = Craft::$app->fields->getLayoutByType(CommentElement::class);
            $this->command->addElementsToLayout($fieldLayout, 'Meta', [
                new CustomField(WpId::get()),
            ]);
            $configData = [$fieldLayout->uid => $fieldLayout->getConfig()];
            Craft::$app->projectConfig->set(CommentsService::CONFIG_FIELDLAYOUT_KEY, $configData);
        });
    }

    public function populate(ElementInterface $element, array $data): void
    {
        /** @var CommentElement $element */
        $element->ownerId = $this->command->importPost($data['post']);
        $element->ownerSiteId = Craft::$app->sites->primarySite->id;
        $element->siteId = Craft::$app->sites->primarySite->id;

        if ($data['author'] && Craft::$app->edition->value >= CmsEdition::Pro->value) {
            try {
                $element->userId = $this->command->import(User::SLUG, $data['author'], [
                    'roles' => 'administrator,editor,author,contributor,viewer,subscriber',
                ]);
            } catch (Throwable) {
            }
        }

        if (!$element->userId) {
            // just see if we have an existing user with this email
            $element->userId = UserElement::find()->email($data['author_email'])->ids()[0] ?? null;
        }

        $element->name = $data['author_name'];
        $element->email = $data['author_email'];
        $element->url = $data['author_url'];
        $element->ipAddress = $data['author_ip'];
        $element->userAgent = $data['author_user_agent'];
        $element->commentDate = DateTimeHelper::toDateTime($data['date_gmt']);
        $element->comment = $data['content']['raw'];
        $element->status = match ($data['status']) {
            'approved' => CommentElement::STATUS_APPROVED,
            default => CommentElement::STATUS_PENDING
        };

        if ($data['parent']) {
            $element->setParentId($this->command->import(self::SLUG, $data['parent']));
        }
    }
}
