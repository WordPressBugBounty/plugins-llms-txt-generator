<?php

if (!defined('ABSPATH')) {
    exit;
}

class LLMS_TXT_GENERATOR_Settings {
    private static $instance = null;
    private $options_name = 'llms_txt_generator_options';
    private $default_options = array(
        'post_types' => array('page'),
    );

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_options_name() {
        return $this->options_name;
    }

    private function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function register_settings() {
        register_setting(
            'llms_txt_generator_options',
            $this->options_name,
            array($this, 'sanitize_options')
        );

        add_settings_section(
            'llms_txt_generator_general',
            'General Settings',
            array($this, 'render_general_section'),
            'llms-txt-generator'
        );

        add_settings_field(
            'post_types',
            'Post Types',
            array($this, 'render_post_types_field'),
            'llms-txt-generator',
            'llms_txt_generator_general'
        );
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to access this page.');
        }

        ?>
        <div class="wrap">
            <h1>LLMs.txt Settings</h1>

            <div class="notice notice-info">
                <p>
                    <strong>🚀 Get more features with Pro version!</strong><br>
                    Visit <a href="https://aeomatic.com" target="_blank">aeomatic.com</a> to:
                    <ul style="list-style-type: disc; margin-left: 20px;">
                        <li>Include Posts in your llms.txt file</li>
                        <li>Daily and Weekly automatic updates</li>
                        <li>Custom taxonomies support</li>
                        <li>Custom generation rules</li>
                        <li>Full Multisite support</li>
                    </ul>
                </p>
            </div>

            <form method="post" action="options.php">
                <?php
                settings_fields('llms_txt_generator_options');
                do_settings_sections('llms-txt-generator');
                submit_button('Save Settings');
                ?>
            </form>
        </div>
        <?php
    }

    public function render_general_section() {
        echo '<p>Configure how the llms.txt file will be generated.</p>';
    }

    public function render_post_types_field() {
        $options = $this->get_options();
        
        $available_post_types = array(
            'page' => array(
                'label' => 'Pages'
            ),
        );
        
        foreach ($available_post_types as $type => $config) {
            $checked = in_array($type, $options['post_types']) ? 'checked' : '';
            ?>
            <label>
                <input type="checkbox" 
                       name="<?php echo esc_attr($this->options_name); ?>[post_types][]" 
                       value="<?php echo esc_attr($type); ?>"
                       <?php echo esc_attr($checked); ?>>
                <?php echo esc_html($config['label']); ?>
            </label><br>
            <?php
        }
        echo '<p class="description">Select which content types should be included in the llms.txt file</p>';
    }

    public function sanitize_options($input) {
        $sanitized = array();
        
        $sanitized['post_types'] = array();
        if (isset($input['post_types']) && is_array($input['post_types'])) {
            foreach ($input['post_types'] as $type) {
                if ($type === 'page') {
                    $sanitized['post_types'][] = 'page';
                }
            }
        }

        if (empty($sanitized['post_types'])) {
            $sanitized['post_types'] = array('page');
        }
        
        return $sanitized;
    }

    public function get_options() {
        $options = get_option($this->options_name);
        return wp_parse_args($options, $this->default_options);
    }
} 