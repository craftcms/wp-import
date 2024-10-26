# wp-import

Prepares a content model and imports WordPress content into Craft CMS.

Supported content types:

- Posts
- Pages
- Media
- Categories
- Tags
- Users
- Comments (requires [Verbb Comments](https://plugins.craftcms.com/comments))
- Custom post types

Supported block types:

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

### 4. Install wp-import

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

You’ll be prompted for your REST API URL, which should be something like `https://example.com/wp-json`. You’ll also need to provide the username and application password you wrote down earlier.

The command will then begin importing your content, creating content model components as needed, such as:

- A “Posts” section for your posts.
- A “Pages” section for your pages.
- An “Uploads” filesystem and volume for your media.
- A “Post Content” CKEditor field with some nested entry types for storing non-HTML block data.

You can import any new content by running the command again later on. Or import just certain posts (etc.) using the `--item-id` option:

```sh
ddev craft wp-import/posts --item-id=123,789
```

By default, any content that was already imported will be skipped. You can instead force content to be re-imported by passing the `--update` option.

```sh
ddev craft wp-import/posts --item-id=123 --update
```

To see a full list of available commands, run:

```sh
ddev craft wp-import --help
```

To see a full list of options for the `wp-imort/all` command (what `wp-import` is aliased to), run:

```sh
ddev craft wp-import/all --help
```
