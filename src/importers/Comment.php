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
use craft\helpers\DateTimeHelper;
use craft\wpimport\BaseImporter;
use Throwable;
use verbb\comments\elements\Comment as CommentElement;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Comment extends BaseImporter
{
    public static function resource(): string
    {
        return 'comments';
    }

    public static function elementType(): string
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

    public function populate(ElementInterface $element, array $data): void
    {
        /** @var CommentElement $element */
        $element->ownerId = $this->command->import(Post::resource(), $data['post']);
        $element->ownerSiteId = Craft::$app->sites->primarySite->id;
        $element->siteId = Craft::$app->sites->primarySite->id;

        if ($data['author'] && Craft::$app->edition->value >= CmsEdition::Pro->value) {
            try {
                $element->userId = $this->command->import(User::resource(), $data['author'], [
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
            $element->setParentId($this->command->import(static::resource(), $data['parent']));
        }
    }
}
