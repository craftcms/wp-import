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
    $toArray = fn($object) => json_decode(json_encode($object), true);

    /** @var WP_Post_Type[] $postTypes */
    $postTypes = array_filter(
        get_post_types([], 'objects'),
        fn(WP_Post_Type $postType) => (
            (!$postType->_builtin || in_array($postType->name, ['post', 'page'])) &&
            strpos($postType->name, 'acf-') !== 0 &&
            strpos($postType->name, 'jp_') !== 0
        )
    );

    foreach ($postTypes as $postType) {
        register_rest_field($postType->name, 'content_parsed', [
            'get_callback' => fn($object) => $toArray(parse_blocks($object['content']['raw'])),
            'schema' => [
                'description' => 'Parsed content for the object.',
                'type' => 'object',
                'context' => ['edit'],
            ],
        ]);
    }

    register_rest_route('craftcms/v1', 'info', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => fn() => [
            'post_types' => array_map(fn(WP_Post_Type $postType) => array_merge($toArray($postType), [
                'supports' => get_all_post_type_supports($postType->name),
            ]), $postTypes),
            'permalink_structure' => get_option('permalink_structure'),
            'color_palette' => wp_get_global_settings(['color', 'palette']),
        ],
        'permission_callback' => fn() => current_user_can('manage_options'),
    ]);

    // h/t https://wordpress.stackexchange.com/a/406877
    register_rest_route('craftcms/v1', 'post/(?P<id>\d+)', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => function(WP_REST_Request $request) {
            $id = (int)$request['id'];
            $post = get_post($id);
            $post_type = get_post_type($post);
            $valid_post_types = get_post_types([
                'public' => true,
                'show_in_rest' => true,
            ]);
            if (!in_array($post_type, $valid_post_types)) {
                return new WP_Error('rest_post_invalid_id', __('Invalid post ID.'), ['status' => 404]);
            }
            $controller = new WP_REST_Posts_Controller($post_type);
            $check = $controller->get_item_permissions_check($request);
            if ($check !== true) {
                return $check;
            }
            return $controller->get_item($request);
        },
        'permission_callback' => fn() => current_user_can('manage_options'),
    ]);
});
