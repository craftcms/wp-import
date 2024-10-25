<?php
/**
 * Plugin Name: wp-import helper
 * Description: Makes additional data available via the REST API for the wp-import Craft CMS extension.
 * Plugin URI:  https://github.com/craftcms/wp-import
 * Version:     1.0
 * Author:      Pixel & Tonic
 * Author URI:  https://craftcms.com
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

defined('ABSPATH') or die;

add_action('rest_api_init', function() {
    $contentParsedCallback = fn($object) => json_decode(json_encode(parse_blocks($object['content']['raw'])), true);
    $contentParsedSchema = [
        'description' => 'Parsed content for the object.',
        'type' => 'object',
        'context' => ['edit'],
    ];

    register_rest_field('post', 'content_parsed', [
        'get_callback' => $contentParsedCallback,
        'schema' => $contentParsedSchema,
    ]);

    register_rest_field('page', 'content_parsed', [
        'get_callback' => $contentParsedCallback,
        'schema' => $contentParsedSchema,
    ]);

    register_rest_route('craftcms/v1', 'settings', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => fn() => [
            'permalink_structure' => get_option('permalink_structure')
        ],
    ]);
});
