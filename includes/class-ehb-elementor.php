<?php

declare(strict_types=1);

namespace EHB;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle Elementor integration and widget registration.
 */
final class Elementor
{

    /**
     * Register hooks.
     */
    public function register(): void
    {
        add_action('elementor/widgets/register', [$this, 'register_widgets']);
    }

    /**
     * Register dynamic widgets.
     */
    public function register_widgets($widgets_manager): void
    {
        $query = new \WP_Query([
            'post_type' => CPT::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => -1,
        ]);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $widgets_manager->register(new Dynamic_Widget(get_the_ID()));
            }
            wp_reset_postdata();
        }
    }
}

/**
 * Dynamic Elementor Widget class.
 */
class Dynamic_Widget extends \Elementor\Widget_Base
{

    private int $post_id;

    public function __construct(int $post_id, array $data = [], array $args = null)
    {
        $this->post_id = $post_id;
        parent::__construct($data, $args);
    }

    public function get_name(): string
    {
        return 'ehb_widget_' . $this->post_id;
    }

    public function get_title(): string
    {
        return get_the_title($this->post_id) ?: 'EHB Widget #' . $this->post_id;
    }

    public function get_icon(): string
    {
        return 'eicon-code';
    }

    public function get_categories(): array
    {
        return ['general'];
    }

    protected function register_controls(): void
    {
        $mappings_json = get_post_meta($this->post_id, '_ehb_mappings', true);
        $mappings = json_decode($mappings_json ?: '[]', true);

        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__('Content', 'elementor-html-builder'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        foreach ($mappings as $mapping) {
            $control_id = $mapping['controlId'];
            $control_type = $mapping['controlType'];
            $tag_name = $mapping['tagName'];

            $label = ucfirst($control_type) . ' (' . $tag_name . ')';

            switch ($control_type) {
                case 'text':
                    $this->add_control(
                        $control_id,
                        [
                            'label' => $label,
                            'type' => \Elementor\Controls_Manager::TEXT,
                            'default' => '',
                        ]
                    );
                    break;
                case 'media':
                    $this->add_control(
                        $control_id,
                        [
                            'label' => $label,
                            'type' => \Elementor\Controls_Manager::MEDIA,
                        ]
                    );
                    break;
                case 'color':
                    $this->add_control(
                        $control_id,
                        [
                            'label' => $label,
                            'type' => \Elementor\Controls_Manager::COLOR,
                        ]
                    );
                    break;
            }
        }

        $this->end_controls_section();
    }

    protected function render(): void
    {
        $settings = $this->get_settings_for_display();
        $html = get_post_meta($this->post_id, '_ehb_html', true);
        $css = get_post_meta($this->post_id, '_ehb_css', true);
        $mappings_json = get_post_meta($this->post_id, '_ehb_mappings', true) ?: '[]';
        $mappings = json_decode($mappings_json, true);

        if (empty($html)) {
            return;
        }

        // Inject CSS
        if (!empty($css)) {
            echo '<style>' . $css . '</style>';
        }

        if (empty($mappings)) {
            echo $html;
            return;
        }

        // Use DOMDocument to inject values based on selectors
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        // Add encoding to handle special characters correctly
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);

        foreach ($mappings as $mapping) {
            $control_id = $mapping['controlId'];
            $control_type = $mapping['controlType'];
            $selector = $mapping['selector'];

            $value = $settings[$control_id] ?? null;

            if (empty($value) && $control_type === 'text') {
                continue;
            }

            // Convert CSS selector to XPath (simple conversion)
            $xpath_selector = $this->css_to_xpath($selector);
            $nodes = $xpath->query($xpath_selector);

            if ($nodes && $nodes->length > 0) {
                foreach ($nodes as $node) {
                    switch ($control_type) {
                        case 'text':
                            $node->nodeValue = esc_html($value);
                            break;
                        case 'media':
                            if (!empty($value['url'])) {
                                if ($node->tagName === 'img') {
                                    $node->setAttribute('src', esc_url($value['url']));
                                } else {
                                    $current_style = $node->getAttribute('style');
                                    $node->setAttribute('style', $current_style . ' background-image: url(' . esc_url($value['url']) . ');');
                                }
                            }
                            break;
                        case 'color':
                            $current_style = $node->getAttribute('style');
                            $node->setAttribute('style', $current_style . ' color: ' . esc_attr($value) . ';');
                            break;
                    }
                }
            }
        }

        echo $dom->saveHTML();
    }

    /**
     * Basic CSS selector to XPath converter.
     */
    private function css_to_xpath(string $selector): string
    {
        $parts = explode(' > ', $selector);
        $xpath = '';

        foreach ($parts as $part) {
            if (str_starts_with($part, '#')) {
                $xpath .= "//*[@id='" . substr($part, 1) . "']";
            } elseif (str_contains($part, ':nth-child(')) {
                preg_match('/(.*):nth-child\((\d+)\)/', $part, $matches);
                $xpath .= '//' . $matches[1] . '[' . $matches[2] . ']';
            } else {
                $xpath .= '//' . $part;
            }
        }

        return $xpath;
    }
}
