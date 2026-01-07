<?php

declare(strict_types=1);

namespace ElementorHTMLBuilder;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Custom Post Type registration for EHB Widgets.
 */
final class EHB_CPT
{

    public const POST_TYPE = 'ehb_widget';

    /**
     * Register the CPT.
     */
    public function register(): void
    {
        add_action('init', [$this, 'register_post_type']);
    }

    /**
     * Actual registration logic.
     */
    public function register_post_type(): void
    {
        $labels = [
            'name' => _x('EHB Widgets', 'post type general name', 'elementor-html-builder'),
            'singular_name' => _x('EHB Widget', 'post type singular name', 'elementor-html-builder'),
            'menu_name' => _x('EHB Widgets', 'admin menu', 'elementor-html-builder'),
            'name_admin_bar' => _x('EHB Widget', 'add new on admin bar', 'elementor-html-builder'),
            'add_new' => _x('Add New', 'ehb_widget', 'elementor-html-builder'),
            'add_new_item' => __('Add New Widget', 'elementor-html-builder'),
            'new_item' => __('New Widget', 'elementor-html-builder'),
            'edit_item' => __('Edit Widget', 'elementor-html-builder'),
            'view_item' => __('View Widget', 'elementor-html-builder'),
            'all_items' => __('All Widgets', 'elementor-html-builder'),
            'search_items' => __('Search Widgets', 'elementor-html-builder'),
            'not_found' => __('No widgets found.', 'elementor-html-builder'),
            'not_found_in_trash' => __('No widgets found in Trash.', 'elementor-html-builder'),
        ];

        $args = [
            'labels' => $labels,
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => ['slug' => self::POST_TYPE],
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => null,
            'supports' => ['title'],
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-plus-alt',
        ];

        register_post_type(self::POST_TYPE, $args);
    }
}
