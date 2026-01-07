<?php

declare(strict_types=1);

namespace EHB;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Loader class to manage plugin initialization.
 */
final class Loader
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
        require_once EHB_PATH . 'includes/class-ehb-elementor.php';
    }

    /**
     * Initialize plugin components.
     */
    private function init_components(): void
    {
        (new CPT())->register();
        (new Admin())->register();
        (new AJAX())->register();
        (new Elementor())->register();
    }
}
