<?php

declare(strict_types=1);

namespace ElementorHTMLBuilder;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle AJAX requests for saving widget data.
 */
final class AJAX
{

    /**
     * Register hooks.
     */
    public function register(): void
    {
        add_action('wp_ajax_ehb_save_widget', [$this, 'save_widget']);
    }

    /**
     * Save widget configuration.
     */
    public function save_widget(): void
    {
        check_ajax_referer('ehb_builder_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        $html = isset($_POST['html']) ? $_POST['html'] : '';
        $css = isset($_POST['css']) ? $_POST['css'] : '';
        $mappings = isset($_POST['mappings']) ? $_POST['mappings'] : '[]';

        // If post_id is 0, create a new ehb_widget
        if (0 === $post_id) {
            $post_id = wp_insert_post([
                'post_type' => CPT::POST_TYPE,
                'post_status' => 'publish',
                'post_title' => __('New Widget', 'elementor-html-builder'),
            ]);
        }

        if (is_wp_error($post_id)) {
            wp_send_json_error('Failed to create widget');
        }

        // Update meta
        update_post_meta($post_id, '_ehb_html', $html);
        update_post_meta($post_id, '_ehb_css', $css);
        update_post_meta($post_id, '_ehb_mappings', $mappings);

        wp_send_json_success([
            'post_id' => $post_id,
            'message' => __('Widget saved successfully', 'elementor-html-builder'),
        ]);
    }
}
