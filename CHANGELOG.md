# Release Notes for WP Import

## 2.0.0 - 2026-11-27

- WP Import now requires Craft CMS 6.0 or later and PHP 8.5 or later, and runs natively on Craft 6 without the `craftcms/yii2-adapter` package. See [UPGRADE.md](UPGRADE.md).
- WordPress categories, tags, and custom taxonomies are now imported as entries, and related to posts with Entries fields.
- Comments are no longer imported.
- The “WordPress Content” volume now uses a filesystem disk, set with the new `--disk` option (`public` by default).
- CKEditor toolbars are now set on each field, rather than in CKEditor configs.
- The `--interactive=0` and `--show-summary=0` options are now `--no-interaction` and `--no-summary`.
- The `--type` option can now be passed more than once.
- Custom importers, block transformers, and ACF adapters now go in `config/craft/wp-import/`, and can use any namespace.
- The package’s classes moved from the `craft\wpimport` namespace to `CraftCms\WpImport`.
- Replaced the `craft\wpimport\Command::EVENT_REGISTER_*` events with `CraftCms\WpImport\Events\ImporterTypesResolving`, `BlockTransformerTypesResolving`, and `AcfAdapterTypesResolving`.
- Added `CraftCms\WpImport\Importers\BaseImporter::existingElementQuery()`.
- Fixed a bug where running the command with `--update` could fail with a “Handle has already been taken” error for ACF fields.
- Fixed an error that occurred when importing Details blocks.

## 1.0.0 - 2026-11-27

- Added Craft 6 compatibility, when the `craftcms/yii2-adapter` package is installed. Fields are created for CKEditor 6. ([#25](https://github.com/craftcms/wp-import/pull/25))
- Added support for CKEditor 5 on Craft 5. ([#25](https://github.com/craftcms/wp-import/pull/25))
- Added support for Symfony 8. ([#25](https://github.com/craftcms/wp-import/pull/25))
- The import now stops with instructions if categories or tags haven’t been enabled (Craft 6). ([#25](https://github.com/craftcms/wp-import/pull/25))
- Fixed an error that occurred when importing Details blocks. ([#25](https://github.com/craftcms/wp-import/pull/25))

## 2026-10-11

> [!WARNING]
> WordPress API and media URLs that resolve to private IP addresses are now rejected, unless their hostname is passed via the `--allowed-private-hosts` option.
> See [Importing from Internal Hosts](https://github.com/craftcms/wp-import#importing-from-internal-hosts) on how to do that.

- Fixed a [medium-severity](https://github.com/craftcms/cms/security/policy#severity--remediation) SSRF vulnerability. (GHSA-6q4m-f5qc-r54f)

## 2024-11-13
- Initial release
