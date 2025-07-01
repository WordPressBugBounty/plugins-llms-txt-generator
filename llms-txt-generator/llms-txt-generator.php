<?php

/**
 * Plugin Name: LLMs.txt Generator
 * Plugin URI: https://aeomatic.pingback.com
 * Description: Automatically generates and manages the llms.txt file for your WordPress site, providing guidelines for LLMs (Large Language Models).
 * Version: 1.0.2
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Author: AEOmatic
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: llms-txt-generator
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('LLMS_TXT_GENERATOR_FILE', __FILE__);
define('LLMS_TXT_GENERATOR_PATH', plugin_dir_path(__FILE__));
define('LLMS_TXT_GENERATOR_URL', plugin_dir_url(__FILE__));
define('LLMS_TXT_GENERATOR_VERSION', '1.0.2');

try {
    // Include required files
    require_once LLMS_TXT_GENERATOR_PATH . 'includes/class-settings.php';
    require_once LLMS_TXT_GENERATOR_PATH . 'includes/class-cache.php';
    require_once LLMS_TXT_GENERATOR_PATH . 'includes/class-dashboard.php';
    require_once LLMS_TXT_GENERATOR_PATH . 'includes/class-multisite.php';
    require_once LLMS_TXT_GENERATOR_PATH . 'includes/class-validator.php';
    require_once LLMS_TXT_GENERATOR_PATH . 'includes/class-access-control.php';

    class LLMS_TXT_GENERATOR {
        private static $instance = null;
        private $settings;
        private $cache;
        private $validator;
        private $multisite;
        private $dashboard;
        private $access_control;

        public static function get_instance() {
            if (null === self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        private function __construct() {
            // Initialize main classes
            $this->settings = LLMS_TXT_GENERATOR_Settings::get_instance();
            $this->cache = LLMS_TXT_GENERATOR_Cache::get_instance();
            $this->validator = LLMS_TXT_GENERATOR_Validator::get_instance();
            $this->dashboard = LLMS_TXT_GENERATOR_Dashboard::get_instance();
            $this->access_control = LLMS_TXT_GENERATOR_Access_Control::get_instance();
            
            // Initialize multisite if needed
            if (is_multisite()) {
                $this->multisite = LLMS_TXT_GENERATOR_Multisite::get_instance();
            }

            // Register hooks
            register_activation_hook(__FILE__, array($this, 'activate'));
            register_deactivation_hook(__FILE__, array($this, 'deactivate'));
            add_action('init', array($this, 'add_rewrite_rules'));
            add_filter('query_vars', array($this, 'add_query_vars'));
            add_action('admin_menu', array($this, 'add_admin_menu'), 10);
            add_action('admin_enqueue_scripts', array($this, 'register_assets'));
            add_action('parse_request', array($this, 'handle_llms_txt_request'));
            add_action('save_post', array($this, 'maybe_update_llms_txt'));
            add_action('edit_term', array($this, 'maybe_update_llms_txt'));
            add_action('delete_term', array($this, 'maybe_update_llms_txt'));

            // Scheduled updates
            add_action('llms_txt_generator_monthly_update', array($this, 'scheduled_update'));

            // AJAX handlers
            add_action('wp_ajax_llms_txt_generator_clear_cache', array($this, 'ajax_clear_cache'));
            add_action('wp_ajax_llms_txt_generator_regenerate_file', array($this, 'ajax_regenerate_file'));
        }

        public function activate($network_wide) {
            if (is_multisite() && $network_wide) {
                $sites = get_sites();
                foreach ($sites as $site) {
                    switch_to_blog($site->blog_id);
                    $this->single_activate();
                    restore_current_blog();
                }
            } else {
                $this->single_activate();
            }
        }

        private function single_activate() {
            // Configurações padrão
            $default_settings = array(
                'post_types' => array('page'),
                'update_frequency' => 'monthly'
            );

            // Salva configurações apenas se não existirem
            if (!get_option('llms_txt_generator_options')) {
                update_option('llms_txt_generator_options', $default_settings);
            }

            // Configura o agendamento inicial
            if (!wp_next_scheduled('llms_txt_generator_monthly_update')) {
                wp_schedule_event(strtotime('first day of next month'), 'monthly', 'llms_txt_generator_monthly_update');
            }

            // Gera o arquivo inicial
            $this->generate_llms_txt();

            // Adiciona regras de rewrite
            $this->add_rewrite_rules();
            flush_rewrite_rules();
        }

        public function deactivate($network_wide) {
            if (is_multisite() && $network_wide) {
                $sites = get_sites();
                foreach ($sites as $site) {
                    switch_to_blog($site->blog_id);
                    $this->single_deactivate();
                    restore_current_blog();
                }
            } else {
                $this->single_deactivate();
            }
        }

        private function single_deactivate() {
            // Remove agendamentos
            wp_clear_scheduled_hook('llms_txt_generator_monthly_update');
            
            // Limpa cache
            $this->cache->clear_all();
            
            // Remove regras de rewrite
            flush_rewrite_rules();
        }

        public function add_query_vars($vars) {
            $vars[] = 'llms_txt';
            return $vars;
        }

        public function add_rewrite_rules() {
            add_rewrite_rule('^llms\.txt$', 'index.php?llms_txt=1', 'top');
            add_rewrite_tag('%llms_txt%', '([^&]+)');
        }

        public function handle_llms_txt_request($wp) {
            if (isset($wp->query_vars['llms_txt'])) {
                $this->serve_llms_txt();
                exit;
            }
        }

        private function serve_llms_txt() {
            // Verifica cache primeiro
            $content = $this->cache->get('llms_txt_content');
            
            if (false === $content) {
                // Gera novo conteúdo
                $content = $this->generate_llms_txt_content();
                
                // Armazena em cache
                $this->cache->set('llms_txt_content', $content);
            }

            // Define headers
            header('Content-Type: text/plain; charset=utf-8');
            header('X-Robots-Tag: noindex, nofollow');
            
            // Output com escape
            echo esc_html($content);
            exit;
        }

        private function generate_llms_txt_content() {
            $options = $this->settings->get_options();
            
            $content = "# " . get_bloginfo('name') . "\n\n";
            $content .= "> " . get_bloginfo('description') . "\n\n";

            // URLs principais
            $content .= "## Main URLs\n\n";
            $content .= "- [Home](" . home_url() . "): Main page\n";

            // Adiciona link do blog apenas se existir uma página de posts configurada
            $page_for_posts = get_option('page_for_posts');
            if ($page_for_posts && $page_for_posts !== '0') {
                $blog_url = get_permalink($page_for_posts);
                if ($blog_url) {
                    $content .= "- [Blog](" . $blog_url . "): Blog posts\n";
                }
            }

            // Páginas - Agora forçando apenas 'page' na versão free
            $posts = get_posts(array(
                'post_type' => 'page', // Forçando apenas páginas
                'posts_per_page' => -1,
                'post_status' => 'publish'
            ));

            if (!empty($posts)) {
                $post_type_obj = get_post_type_object('page');
                $content .= "\n## " . $post_type_obj->labels->name . "\n\n";

                foreach ($posts as $post) {
                    $content .= "- [" . $post->post_title . "](" . get_permalink($post) . ")\n";
                }
            }

            $content = wp_kses_post($content);
            return $content;
        }

        public function maybe_update_llms_txt() {
            $this->generate_llms_txt();
        }

        private function generate_llms_txt() {
            $content = $this->generate_llms_txt_content();
            
            // Validate content if validator is available
            if ($this->validator) {
                $validation_results = $this->validator->validate_content($content);
                if (!$validation_results['is_valid']) {
                    set_transient(
                        'llms_txt_validation_errors',
                        $validation_results['errors'],
                        HOUR_IN_SECONDS
                    );
                }
            }
            
            // Limpa cache
            $this->cache->delete('llms_txt_content');
            
            // Armazena novo conteúdo em cache
            $this->cache->set('llms_txt_content', $content);
            
            // Atualiza timestamp da última atualização
            update_option('llms_txt_generator_last_update', current_time('mysql'));
            
            return true;
        }

        public function add_admin_menu() {
            // Add main menu (Dashboard)
            add_menu_page(
                'LLMs.txt',              // Page title
                'LLMs.txt',              // Menu title
                'manage_options',         // Capability
                'llms-txt-generator',     // Menu slug
                array($this->dashboard, 'render_dashboard_page'), // Callback function
                'dashicons-text',         // Icon
                100                      // Position
            );

            // Add Settings submenu
            add_submenu_page(
                'llms-txt-generator',     // Parent slug
                'LLMs.txt Settings',      // Page title
                'Settings',               // Menu title
                'manage_options',         // Capability
                'llms-txt-generator-settings',  // Menu slug
                array($this->settings, 'render_settings_page') // Callback function
            );
        }

        public function register_assets($hook) {
            if (!in_array($hook, array(
                'toplevel_page_llms-txt-generator',
                'llms-txt_page_llms-txt-generator-settings'
            ))) {
                return;
            }

            wp_enqueue_style(
                'llms-txt-generator-admin',
                LLMS_TXT_GENERATOR_URL . 'assets/css/admin.css',
                array(),
                LLMS_TXT_GENERATOR_VERSION
            );

            wp_enqueue_script(
                'llms-txt-generator-admin',
                LLMS_TXT_GENERATOR_URL . 'assets/js/admin.js',
                array('jquery'),
                LLMS_TXT_GENERATOR_VERSION,
                true
            );

            wp_localize_script('llms-txt-generator-admin', 'wpLLMsTxt', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('llms_txt_generator_admin'),
                'isMultisite' => is_multisite()
            ));
        }

        public function ajax_clear_cache() {
            // Verifica nonce
            if (!check_ajax_referer('llms_txt_generator_admin', 'nonce', false)) {
                wp_send_json_error('Invalid nonce');
                return;
            }

            // Verifica permissões
            if (!current_user_can('manage_options')) {
                wp_send_json_error('Permission denied');
                return;
            }

            // Clear cache
            $cleared = $this->cache->clear_all();

            if ($cleared !== false) {
                wp_send_json_success('Cache cleared successfully');
            } else {
                wp_send_json_error('Failed to clear cache');
            }
        }

        public function ajax_regenerate_file() {
            // Verifica nonce
            if (!check_ajax_referer('llms_txt_generator_admin', 'nonce', false)) {
                wp_send_json_error('Invalid nonce');
                return;
            }

            // Verifica permissões
            if (!current_user_can('manage_options')) {
                wp_send_json_error('Permission denied');
                return;
            }

            // Regenerate file
            $result = $this->generate_llms_txt();

            if ($result) {
                wp_send_json_success('File regenerated successfully');
            } else {
                wp_send_json_error('Failed to regenerate file');
            }
        }

        public function scheduled_update() {
            $this->generate_llms_txt();
        }
    }

    // Initialize the plugin
    LLMS_TXT_GENERATOR::get_instance();

} catch (Exception $e) {
    add_action('admin_notices', function() use ($e) {
        ?>
        <div class="notice notice-error">
            <p>Error activating WP LLMs.txt plugin: <?php echo esc_html($e->getMessage()); ?></p>
        </div>
        <?php
    });
}
