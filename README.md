# wp-import

Import all your WordPress content, media, and users into Craft CMS with a single CLI command.

## Table of Contents

- [WordPress Feature Support](#wordpress-feature-support)
- [Requirements](#requirements)
- [Setup](#setup)
- [Usage](#usage)
- [Supported Block Types](#supported-block-types)
- [Supported ACF Fields](#supported-acf-fields)
- [Extending](#extending)
- [Getting Help](#getting-help)

## WordPress Feature Support

Feature | Support
------- | ---------
Posts | ✅
Pages | ✅
Media | ✅
Categories | ✅
Tags | ✅
Users | ✅
Comments | ✅ (requires [Verbb Comments](https://plugins.craftcms.com/comments))
Gutenberg | ✅ (see [Supported Block Types](#supported-block-types))
Custom post types | ✅
Custom taxonomies | ✅
ACF Fields | ✅ (see [Supported ACF Fields](#supported-acf-fields))

## Requirements

Craft 5.5+ is required, as well as the [CKEditor](https://plugins.craftcms.com/ckeditor) plugin. [Verbb Comments](https://plugins.craftcms.com/comments) is also required if you wish to import user comments.

## Setup

### 1. Install the wp-import helper plugin

The import makes use of WordPress’ REST API. The API has *almost* everything we need out of the box, except for a couple things. To fix that, you’ll need to install a simple WordPress plugin that exposes some additional data to authenticated API requests.

To do that, save [plugins/wp-import-helper.php](plugins/wp-import-helper.php) to the `wp-content/plugins/` folder within your WordPress site. Then log into your WP Admin Dashboard and navigate to **Plugins**. Press **Activate** for the “wp-import helper” plugin.

### 2. Create an application password

Within your WP Admin Dashboard, navigate to **Users** and press **Edit** for an administrator’s user account. Scroll down to the “Application Passwords” section, and type “Craft CMS” into the “New Application Password Name” input. Then press **Add New Application Password**.

Write down the username and generated application password somewhere safe. You’ll need it when running the import.

### 3. Include custom post types in the REST API

If you have any custom post types you’d like to be imported, you’ll need to [register them with the REST API](https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-rest-api-support-for-custom-content-types/), by setting `'show_in_rest' => true` in the arguments passed to `register_post_type()`.

### 4. Include ACF fields in the REST API

If you’re using Advanced Custom Fields, you’ll need to [opt into including your field groups in the REST API](https://www.advancedcustomfields.com/resources/wp-rest-api-integration/#enabling-the-rest-api-for-your-acf-fields) by enabling their “Show in REST API” setting. 

### 5. Install wp-import

You’ll first need to install Craft 5.5, which is still in development. To do that, change your `craftcms/cms` requirement in `composer.json` to:

```json
"craftcms/cms": "5.5.x-dev as 5.5.0-alpha",
```

Then run the following CLI commands:

```sh
> ddev composer update craftcms/cms -w
> ddev craft up
> ddev composer require craftcms/wp-import --dev
```

> [!NOTE]
> If you get the following prompt, make sure to answer `y`:
>
> ```sh
> yiisoft/yii2-composer contains a Composer plugin which is currently not in your allow-plugins config. See https://getcomposer.org/allow-plugins
> Do you trust "yiisoft/yii2-composer" to execute code and wish to enable it now? (writes "allow-plugins" to composer.json)
> ```

## Usage

Run the following CLI command to initiate the import:

```sh
ddev craft wp-import
```

You’ll be prompted for your WordPress site URL, as well as the username and application password you wrote down earlier.

The command will then begin importing your content, creating content model components as needed, such as:

- A “Posts” section for your posts.
- A “Pages” section for your pages.
- An “Uploads” filesystem and volume for your media.
- A “Post Content” CKEditor field with some nested entry types for storing non-HTML block data.

You can import any new content by running the command again later on. Or import just certain posts (etc.) using the `--item-id` option:

```sh
ddev craft wp-import --type=post --item-id=123,789
```

By default, any content that was already imported will be skipped. You can instead force content to be re-imported by passing the `--update` option.

```sh
ddev craft wp-import --type=post --item-id=123 --update
```

To see a full list of available options, run:

```sh
ddev craft wp-import --help
```

## Supported Block Types

- `core/audio`
- `core/block`
- `core/button`
- `core/buttons`
- `core/code`
- `core/column`
- `core/columns`
- `core/cover`
- `core/details`
- `core/embed`
- `core/gallery`
- `core/group`
- `core/heading`
- `core/html`
- `core/image`
- `core/list-item`
- `core/list`
- `core/more`
- `core/paragraph`
- `core/preformatted`
- `core/quote`
- `core/separator`
- `core/table`
- `core/video`
- `core-embed/twitter`
- `core-embed/vimeo`
- `core-embed/youtube`
- `cp/codepen-gutenberg-embed-block`
- `dsb/details-summary-block`
- `jetpack/slideshow`
- `jetpack/tiled-gallery`
- `videopress/video`

## Supported ACF Fields:

- Accordion
- Button Group
- Checkbox
- Color Picker
- Date Picker
- Date Time Picker
- Email
- File
- Icon Picker
- Image
- Link
- Message
- Number
- Page Link
- Post Object
- Radio Button
- Range
- Relationship
- Select
- Tab
- Taxonomy
- Text
- Text Area
- Time Picker
- True / False
- URL
- User
- WYSIWYG Editor
- oEmbed

## Extending

There are three types of components that help with importing:

- [Importers](#importers)
- [Block transformers](#block-transformers)
- [ACF adapters](#acf-adapters)

If your WordPress site contains unsupported content types, Gutenberg block types, or ACF field types, you can create your own importers, block transformers, or ACF adapters to fill the gaps.

### Importers

Importers represent the high level types of data that will be imported (posts, pages, users, media, etc.). They identify where their data lives within the WordPress REST API, and populate new Craft elements with incoming data.

Custom importers can be placed within `config/wp-import/importers/`. They must extend `craft\wpimport\BaseImporter`. See [src/importers/](./src/importers) for built-in examples.

### Block Transformers

Block transformers are responsible for converting Gutenberg block data into HTML or a nested entry for the “Post Content” CKEditor field.

Custom block transformers can be placed within `config/wp-import/blocktransformers/`. They must extend `craft\wpimport\BaseBlockTransformer`. See [src/blocktransformers/](./src/blocktransformers) for built-in examples.

### ACF Adapters

ACF adapters create custom fields that map to ACF fields, and convert incoming field values into a format that the Craft field can understand.

Custom ACF adapters can be placed within `config/wp-import/acfadapters/`. They must extend `craft\wpimport\BaseAcfAdapter`. See [src/acfadapters/](./src/acfadapters) for built-in examples.

## Getting Help

If you have any questions or suggestions, you can reach us at [support@craftcms.com](mailto:support@craftcms.com) or [post a GitHub issue](https://github.com/craftcms/wp-import/issues). We’ll do what we can to get you up and running with Craft!
