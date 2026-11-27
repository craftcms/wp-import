<?php

declare(strict_types=1);

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace CraftCms\WpImport\Generators\Fields;

use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Entries;
use CraftCms\WpImport\Console\ImportCommand;
use CraftCms\WpImport\Importers\Tag;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Tags extends BaseFieldGenerator
{
    protected static function uid(): string
    {
        return '5a524179-b073-4ec2-a219-674143dbc455';
    }

    protected static function create(): FieldInterface
    {
        /** @var Tag $importer */
        $importer = app(ImportCommand::class)->importers[Tag::SLUG];
        $field = new Entries;
        $field->name = 'Tags';
        $field->handle = 'tags';
        $field->sources = [sprintf('section:%s', $importer->taxonomy()->section()->uid)];

        return $field;
    }
}
