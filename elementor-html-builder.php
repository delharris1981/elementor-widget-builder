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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define constants
define( 'EHB_VERSION', '1.0.0' );
define( 'EHB_PATH', plugin_dir_path( __FILE__ ) );
define( 'EHB_URL', plugin_dir_url( __FILE__ ) );
define( 'EHB_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Initialize the plugin.
 */
function ehb_init(): void {
	require_once EHB_PATH . 'includes/class-ehb-loader.php';

	$loader = new Loader();
	$loader->run();
}

add_action( 'plugins_loaded', 'EHB\ehb_init' );
