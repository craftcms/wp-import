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
use craft\helpers\StringHelper;
use craft\wpimport\BaseImporter;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class User extends BaseImporter
{
    public static function resource(): string
    {
        return 'users';
    }

    public static function queryParams(): array
    {
        return [
            'roles' => 'administrator,editor,author,contributor',
        ];
    }

    public static function elementType(): string
    {
        return UserElement::class;
    }

    public function supported(?string &$reason): bool
    {
        if (Craft::$app->edition->value < CmsEdition::Pro->value) {
            $reason = 'Craft Pro is required.';
            return false;
        }
        return true;
    }

    public function find(array $data): ?ElementInterface
    {
        return UserElement::find()->email($data['email'])->one();
    }

    public function populate(ElementInterface $element, array $data): void
    {
        if (UserElement::find()->username($data['username'])->exists()) {
            $data['username'] .= '_' . StringHelper::randomString(5);
        }

        /** @var UserElement $element */
        $element->username = $data['username'];
        $element->firstName = $data['first_name'];
        $element->lastName = $data['last_name'];
        $element->email = $data['email'];
        $element->admin = in_array('administrator', $data['roles']);
    }
}
