<?php

declare(strict_types=1);

namespace ElementorHTMLBuilder;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin interface for the EHB Builder.
 */
final class Admin
{

    /**
     * Register hooks.
     */
    public function register(): void
    {
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Add the builder menu page.
     */
    public function add_menu_page(): void
    {
        add_submenu_page(
            'edit.php?post_type=' . CPT::POST_TYPE,
            __('Widget Builder', 'elementor-html-builder'),
            __('Widget Builder', 'elementor-html-builder'),
            'manage_options',
            'ehb-builder',
            [$this, 'render_builder_page']
        );
    }

    /**
     * Enqueue scripts and styles for the builder.
     */
    public function enqueue_assets(string $hook): void
    {
        if ('ehb_widgets_page_ehb-builder' !== $hook) {
            return;
        }

        $asset_file_path = EHB_PATH . 'build/index.asset.php';

        if (!file_exists($asset_file_path)) {
            return;
        }

        $asset_file = include $asset_file_path;

        wp_enqueue_script(
            'ehb-builder',
            EHB_URL . 'build/index.js',
            $asset_file['dependencies'],
            $asset_file['version'],
            true
        );

        wp_localize_script(
            'ehb-builder',
            'ehbBuilderData',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ehb_builder_nonce'),
            ]
        );

        wp_enqueue_style(
            'ehb-builder',
            EHB_URL . 'build/style-index.css',
            [],
            $asset_file['version']
        );
    }

    /**
     * Render the React root element for the builder.
     */
    public function render_builder_page(): void
    {
        ?>
        <div id="ehb-builder-root">
            <h1>
                <?php esc_html_e('Elementor HTML Builder', 'elementor-html-builder'); ?>
            </h1>
            <p>
                <?php esc_html_e('Loading builder...', 'elementor-html-builder'); ?>
            </p>
        </div>
        <?php
    }
}
