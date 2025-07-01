<?php

if (!defined('ABSPATH')) {
    exit;
}

class LLMS_TXT_GENERATOR_Dashboard {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        
    }

    public function render_dashboard_page() {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to access this page.');
        }

        $stats = $this->get_stats();
        ?>
        <div class="wrap">
            <h1>LLMs.txt Dashboard</h1>
            
            <div class="notice notice-info">
                <p>
                    <strong>🚀 Upgrade to Premium!</strong><br>
                    Get access to advanced features:
                    <ul style="list-style-type: disc; margin-left: 20px;">
                        <li>Include Posts in your llms.txt file</li>
                        <li>Daily and Weekly automatic updates</li>
                        <li>Custom taxonomies support</li>
                        <li>Custom generation rules</li>
                        <li>Full Multisite support</li>
                    </ul>
                    <a href="https://aeomatic.com" target="_blank" class="button button-primary">
                        Get Premium Version
                    </a>
                </p>
            </div>

            <div class="card">
                <h2>Content Statistics</h2>
                <?php foreach ($stats as $key => $value): 
                    if ($key !== 'last_update'):
                        $label = ucwords(str_replace('_', ' ', $key));
                ?>
                    <p><?php echo esc_html($label); ?>: <?php echo esc_html($value); ?></p>
                <?php 
                    endif;
                endforeach; ?>
            </div>

            <div class="card">
                <h2>File Management</h2>
                <p>
                    <strong>Last Update:</strong> <?php echo esc_html($stats['last_update']); ?>
                </p>
                <p>
                    <button type="button" class="button button-primary" id="llms-txt-generator-regenerate">
                        Regenerate File
                    </button>
                    <a href="<?php echo esc_url(home_url('/llms.txt')); ?>" target="_blank" class="button">
                        View llms.txt
                    </a>
                </p>
            </div>
        </div>
        <?php
    }

    private function get_stats() {
        $options = LLMS_TXT_GENERATOR_Settings::get_instance()->get_options();
        $stats = array();

        foreach ($options['post_types'] as $post_type) {
            $count = wp_count_posts($post_type);
            $stats['total_' . $post_type . 's'] = $count->publish;
        }

        $last_update = get_option('llms_txt_generator_last_update');
        $stats['last_update'] = $last_update ? $last_update : 'Never';

        return $stats;
    }
} 