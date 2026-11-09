<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\Importers;

use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\WpImport\Generators\Fields\WpId;
use CraftCms\WpImport\Generators\Fields\WpTitle;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class User extends BaseImporter
{
    public const SLUG = 'user';

    public const ALL_ROLES = 'administrator,editor,author,contributor,viewer,subscriber';

    public function slug(): string
    {
        return self::SLUG;
    }

    public function apiUri(): string
    {
        return 'wp/v2/users';
    }

    public function label(): string
    {
        return 'Users';
    }

    public function queryParams(): array
    {
        return [
            'roles' => 'administrator,editor,author,contributor',
        ];
    }

    public function elementType(): string
    {
        return UserElement::class;
    }

    public function supported(?string &$reason): bool
    {
        if (! Edition::isAtLeast(Edition::Pro)) {
            $reason = 'Craft Pro is required.';

            return false;
        }

        return true;
    }

    public function prep(): void
    {
        $this->command->do('Updating the user field layout', function () {
            $fieldLayout = Fields::getLayoutByType(UserElement::class);
            $this->command->addElementsToLayout($fieldLayout, 'Meta', [
                new CustomField(WpId::get()),
                new CustomField(WpTitle::get()),
            ]);
            Users::saveLayout($fieldLayout);
        });
    }

    public function find(array $data): ?ElementInterface
    {
        return UserElement::find()->email($data['email'])->one();
    }

    public function populate(ElementInterface $element, array $data): void
    {
        if (UserElement::find()->username($data['username'])->exists()) {
            $data['username'] .= '_'.Str::random(5);
        }

        /** @var UserElement $element */
        $element->username = str_replace(' ', '', $data['username']);
        $element->firstName = $data['first_name'];
        $element->lastName = $data['last_name'];
        $element->email = $data['email'];
        $element->admin = in_array('administrator', $data['roles']);
    }
}
