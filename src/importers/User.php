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
use craft\helpers\StringHelper;
use craft\models\FieldLayoutTab;
use craft\wpimport\BaseImporter;
use craft\wpimport\generators\fields\WpId;

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

    public function prep(): void
    {
        $fieldLayout = Craft::$app->fields->getLayoutByType(UserElement::class);
        $field = WpId::get();
        if (!$fieldLayout->getFieldById($field->id)) {
            $this->command->do('Updating the user field layout', function() use ($fieldLayout, $field) {
                $tabs = $fieldLayout->getTabs();
                $tabs[] = new FieldLayoutTab([
                    'name' => 'WordPress',
                    'layout' => $fieldLayout,
                    'elements' => [
                        new CustomField($field),
                    ],
                ]);
                $fieldLayout->setTabs($tabs);
                Craft::$app->users->saveLayout($fieldLayout);
            });
        }
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
