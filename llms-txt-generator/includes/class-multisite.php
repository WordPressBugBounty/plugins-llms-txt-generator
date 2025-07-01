<?php

if (!defined('ABSPATH')) {
    exit;
}

class LLMS_TXT_GENERATOR_Multisite {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('network_admin_menu', array($this, 'add_network_menu'));
        add_action('wpmu_new_blog', array($this, 'new_site'), 10, 6);
        add_action('delete_blog', array($this, 'delete_site'), 10, 2);
    }

    public function add_network_menu() {
        add_submenu_page(
            'settings.php',
            'LLMs.txt Network Settings',
            'LLMs.txt',
            'manage_network_options',
            'llms-txt-generator-network',
            array($this, 'render_network_settings_page')
        );
    }

    public function render_network_settings_page() {
        if (!current_user_can('manage_network_options')) {
            wp_die('You do not have permission to access this page.');
        }

        ?>
        <div class="wrap">
            <h1>LLMs.txt Network Settings</h1>

            <div class="notice notice-info">
                <p>
                    <strong>🚀 Get more features with Pro version!</strong><br>
                    Visit <a href="https://aeomatic.com" target="_blank">aeomatic.com</a> to:
                    <ul style="list-style-type: disc; margin-left: 20px;">
                        <li>Network-wide settings management</li>
                        <li>Site-specific customization options</li>
                        <li>Advanced multisite features</li>
                    </ul>
                </p>
            </div>

            <div class="card">
                <h2>Network Overview</h2>
                <?php $this->render_network_stats(); ?>
            </div>
        </div>
        <?php
    }

    private function render_network_stats() {
        $sites = get_sites();
        $total_sites = count($sites);
        $total_pages = 0;

        foreach ($sites as $site) {
            switch_to_blog($site->blog_id);
            
            // Conta páginas
            $count = wp_count_posts('page');
            $total_pages += $count->publish;

            restore_current_blog();
        }

        ?>
        <p><strong>Total Sites:</strong> <?php echo esc_html($total_sites); ?></p>
        <p><strong>Total Pages:</strong> <?php echo esc_html($total_pages); ?></p>
        <?php
    }

    public function new_site($blog_id, $user_id, $domain, $path, $site_id, $meta) {
        switch_to_blog($blog_id);
        
        $default_settings = array(
            'post_types' => array('page')
        );
        
        update_option('llms_txt_generator_options', $default_settings);
        
        restore_current_blog();
    }

    public function delete_site($blog_id, $drop) {
        if ($drop) {
            switch_to_blog($blog_id);
            delete_option('llms_txt_generator_options');
            restore_current_blog();
        }
    }
} 