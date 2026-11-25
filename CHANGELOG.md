# Release Notes for WP Import

## Unreleased

- Added Craft 6 compatibility, when the `craftcms/yii2-adapter` package is installed. Fields are created for CKEditor 6.
- Added support for Symfony 8.
- Fixed an error that occurred when importing Details blocks.

## 2026-10-11

> [!WARNING]
> WordPress API and media URLs that resolve to private IP addresses are now rejected, unless their hostname is passed via the `--allowed-private-hosts` option.
> See [Importing from Internal Hosts](https://github.com/craftcms/wp-import#importing-from-internal-hosts) on how to do that.

- Fixed a [medium-severity](https://github.com/craftcms/cms/security/policy#severity--remediation) SSRF vulnerability. (GHSA-6q4m-f5qc-r54f)

## 2024-11-13
- Initial release
