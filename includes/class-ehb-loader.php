<?php

declare(strict_types=1);

namespace ElementorHTMLBuilder;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Loader class to manage plugin initialization.
 */
final class EHB_Loader
{

    /**
     * Run the loader.
     */
    public function run(): void
    {
        $this->define_dependencies();
        $this->init_components();
    }

    /**
     * Include necessary files.
     */
    private function define_dependencies(): void
    {
        require_once EHB_PATH . 'includes/class-ehb-cpt.php';
        require_once EHB_PATH . 'includes/class-ehb-admin.php';
        require_once EHB_PATH . 'includes/class-ehb-ajax.php';

        if (did_action('elementor/loaded')) {
            require_once EHB_PATH . 'includes/class-ehb-elementor.php';
        }
    }

    /**
     * Initialize plugin components.
     */
    private function init_components(): void
    {
        (new EHB_CPT())->register();
        (new EHB_Admin())->register();
        (new EHB_AJAX())->register();

        if (did_action('elementor/loaded')) {
            (new EHB_Elementor())->register();
        }
    }
}
