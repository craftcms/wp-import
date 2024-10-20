<?php
/**
 * Plugin Name: Parse Blocks
 * Description: Parses the blocks out of content HTML for REST API responses.
 * Plugin URI:  https://github.com/craftcms/wp-import
 * Version:     1.0
 * Author:      Pixel & Tonic
 * Author URI:  https://craftcms.com
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

defined('ABSPATH') or die;

add_action('rest_api_init', function() {
    $post_content_raw_schema = [
        'description' => 'Parsed content for the object.',
        'type' => 'object',
        'context' => ['edit'],
    ];

    register_rest_field(
        'post',
        'content_parsed',
        [
            'get_callback' => 'show_post_content_parsed',
            'schema' => $post_content_raw_schema,
        ]
    );

    register_rest_field(
        'page',
        'content_parsed',
        [
            'get_callback' => 'show_post_content_parsed',
            'schema' => $post_content_raw_schema,
        ]
    );
});

function show_post_content_parsed($object, $field_name, $request)
{
    return json_decode(json_encode(parse_blocks($object['content']['raw'])), true);
}
