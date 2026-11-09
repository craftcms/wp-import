# Upgrading to wp-import 3.0 (Craft 6)

wp-import 3.0 runs natively on Craft 6. This guide lists what changed from 2.x.

## Requirements

- Craft CMS 6.0 or later
- PHP 8.5 or later
- CKEditor for Craft 6
- The `craftcms/yii2-adapter` package is not needed.

## Upgrade steps

1. Upgrade your project to Craft 6 first.
2. Update the package: `composer require craftcms/wp-import:^3.0 --dev`.
3. If you wrote custom importers, block transformers or ACF adapters, move them from `config/wp-import/` to `config/craft/wp-import/`, and update them as described below.

wp-import is now a Laravel package. Composer’s package discovery registers its command, so the `yiisoft/yii2-composer` plugin prompt from 2.x no longer appears.

## Breaking changes

### Running the command

`php craft wp-import` still works, and so do `php artisan wp-import` and `php craft wp-import/import`. Some options changed:

| Before | After |
| --- | --- |
| `--interactive=0` | `--no-interaction` |
| `--show-summary=0` | `--no-summary` |
| `--type=post,page` | `--type=post,page` or `--type=post --type=page` |
| (none) | `--disk=<name>`: the filesystem disk for the “WordPress Content” volume (defaults to `public`) |

The command’s output uses Laravel Prompts, so it looks different from 2.x.

### Content model

- **Categories, tags and custom taxonomies are imported as entries.** Craft 6 has no categories or tags without the Yii 2 adapter. Hierarchical taxonomies get a Structure section and flat ones a Channel section, and tags go to a “Tags” channel. Posts relate to them with Entries fields, which keep the handles 2.x used (`categories`, `tags`, …).
- **Comments aren’t imported.** Verbb Comments isn’t available natively on Craft 6 yet. WordPress comments are skipped.
- **No filesystem is created.** Craft 6 volumes use Laravel filesystem disks. The “WordPress Content” volume uses the disk passed with `--disk` (default `public`), with a `wp-content` subpath.
- **No CKEditor configs are created.** CKEditor for Craft 6 stores toolbars on each field, so the Post Content, Pull Quote and Rich Caption fields (and fields made for ACF WYSIWYG fields) set their own toolbars.

### Events

The Yii events on the command are replaced by Laravel events. They pass a collection of class names you can add to.

| Before | After |
| --- | --- |
| `craft\wpimport\Command::EVENT_REGISTER_IMPORTERS` | `CraftCms\WpImport\Events\ImporterTypesResolving` |
| `craft\wpimport\Command::EVENT_REGISTER_BLOCK_TRANSFORMERS` | `CraftCms\WpImport\Events\BlockTransformerTypesResolving` |
| `craft\wpimport\Command::EVENT_REGISTER_ACF_ADAPTERS` | `CraftCms\WpImport\Events\AcfAdapterTypesResolving` |

```php
// Before
use craft\events\RegisterComponentTypesEvent;
use craft\wpimport\Command;
use yii\base\Event;

if (class_exists(Command::class)) {
    Event::on(Command::class, Command::EVENT_REGISTER_BLOCK_TRANSFORMERS, function(RegisterComponentTypesEvent $event) {
        $event->types[] = MyBlockTransformer::class;
    });
}

// After
use CraftCms\WpImport\Events\BlockTransformerTypesResolving;
use Illuminate\Support\Facades\Event;

Event::listen(function (BlockTransformerTypesResolving $event): void {
    $event->types->push(MyBlockTransformer::class);
});
```

### Custom components

Components no longer extend `yii\base\BaseObject`. Their constructors take only the command (no `$config` array), and they work with Craft 6 classes: for example `CraftCms\Cms\Entry\Elements\Entry` instead of `craft\elements\Entry`, and `CraftCms\Cms\Field\Contracts\FieldInterface` instead of `craft\base\FieldInterface`.

```php
// Before
namespace craft\wpimport\blocktransformers;

use craft\elements\Entry;
use craft\wpimport\BaseBlockTransformer;

class MyBlock extends BaseBlockTransformer { /* ... */ }

// After (config/craft/wp-import/blocktransformers/MyBlock.php)
namespace App\WpImport;

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\WpImport\BlockTransformers\BaseBlockTransformer;

class MyBlock extends BaseBlockTransformer
{
    public static function blockName(): string
    {
        return 'acme/my-block';
    }

    public function render(array $data, Entry $entry): string
    {
        return $data['innerHTML'];
    }
}
```

Files in `config/craft/wp-import/<importers|blocktransformers|acfadapters>/` are loaded automatically and can use any namespace. Components registered through the events must be autoloadable.

### Classes and namespaces

| Before | After |
| --- | --- |
| `craft\wpimport\Command` | `CraftCms\WpImport\Console\ImportCommand` |
| `craft\wpimport\Extension` | `CraftCms\WpImport\WpImportServiceProvider` |
| `craft\wpimport\BaseImporter` | `CraftCms\WpImport\Importers\BaseImporter` |
| `craft\wpimport\BaseConfigurableImporter` | `CraftCms\WpImport\Importers\BaseConfigurableImporter` |
| `craft\wpimport\BaseBlockTransformer` | `CraftCms\WpImport\BlockTransformers\BaseBlockTransformer` |
| `craft\wpimport\BaseAcfAdapter` | `CraftCms\WpImport\AcfAdapters\BaseAcfAdapter` |
| `craft\wpimport\importers\*` | `CraftCms\WpImport\Importers\*` |
| `craft\wpimport\blocktransformers\*` | `CraftCms\WpImport\BlockTransformers\*` |
| `craft\wpimport\acfadapters\*` | `CraftCms\WpImport\AcfAdapters\*` |
| `craft\wpimport\errors\*` | `CraftCms\WpImport\Exceptions\*` |
| `craft\wpimport\generators\entrytypes\*` | `CraftCms\WpImport\Generators\EntryTypes\*` |
| `craft\wpimport\generators\fields\*` | `CraftCms\WpImport\Generators\Fields\*` |
| `craft\wpimport\generators\volumes\*` | `CraftCms\WpImport\Generators\Volumes\*` |
| `craft\wpimport\importers\Comment` | Removed. |
| `craft\wpimport\generators\fields\Comments` | Removed. |
| `craft\wpimport\generators\ckeconfigs\*` | Removed. Toolbars are set on the fields. |
| `craft\wpimport\generators\filesystems\*` | Removed. The volume uses a filesystem disk. |
| `craft\wpimport\generators\taggroups\*` | Removed. Tags are entries in a “Tags” section. |

`BaseImporter` has a new `existingElementQuery()` method, which returns the query used to find items that were already imported. Importers that save entries should scope it to their section, because WordPress post and term IDs overlap.

## Need help?

Reach us at [support@craftcms.com](mailto:support@craftcms.com) or [post a GitHub issue](https://github.com/craftcms/wp-import/issues).
