<?php
/**
 * Plugin Name: Elementor HTML Builder
 * Description: Build dynamic Elementor widgets using raw HTML and CSS.
 * Version: 1.0.0
 * Author: Antigravity
 * Text Domain: elementor-html-builder
 * Requires PHP: 8.2
 * Requires at least: 6.0
 */

declare(strict_types=1);

namespace EHB;

if (!defined('ABSPATH')) {
	exit;
}

// Define constants
define('EHB_VERSION', '1.0.0');
define('EHB_PATH', plugin_dir_path(__FILE__));
define('EHB_URL', plugin_dir_url(__FILE__));
define('EHB_BASENAME', plugin_basename(__FILE__));

/**
 * Initialize the plugin.
 */
function ehb_init(): void
{
	require_once EHB_PATH . 'includes/class-ehb-loader.php';

	// Check for Elementor
	if (!did_action('elementor/loaded')) {
		add_action('admin_notices', 'EHB\ehb_missing_elementor_notice');
		return;
	}

	$loader = new Loader();
	$loader->run();
}

/**
 * Admin notice if Elementor is not active.
 */
function ehb_missing_elementor_notice(): void
{
	if (!current_user_can('activate_plugins')) {
		return;
	}

	$message = sprintf(
		/* translators: 1: Plugin name 2: Elementor */
		esc_html__('%1$s requires %2$s to be installed and activated.', 'elementor-html-builder'),
		'<strong>' . esc_html__('Elementor HTML Builder', 'elementor-html-builder') . '</strong>',
		'<strong>' . esc_html__('Elementor', 'elementor-html-builder') . '</strong>'
	);

	printf('<div class="notice notice-warning is-dismissible"><p>%s</p></div>', $message);
}

add_action('plugins_loaded', 'EHB\ehb_init');
