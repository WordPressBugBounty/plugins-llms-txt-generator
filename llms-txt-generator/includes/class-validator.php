<?php

if (!defined('ABSPATH')) {
    exit;
}

class LLMS_TXT_GENERATOR_Validator {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Initialize if needed
    }

    /**
     * Validate the content of llms.txt
     * 
     * @param string $content The content to validate
     * @return array Validation results with 'is_valid', 'errors', and 'warnings'
     */
    public function validate_content($content) {
        $results = array(
            'is_valid' => true,
            'errors' => array(),
            'warnings' => array()
        );

        // Check if content is empty
        if (empty($content)) {
            $results['is_valid'] = false;
            $results['errors'][] = 'Content is empty';
            return $results;
        }

        // Check basic structure
        if (!$this->has_valid_structure($content)) {
            $results['is_valid'] = false;
            $results['errors'][] = 'Invalid markdown structure';
        }

        // Check links
        $invalid_links = $this->validate_links($content);
        if (!empty($invalid_links)) {
            $results['is_valid'] = false;
            foreach ($invalid_links as $link) {
                $results['errors'][] = "Invalid link: {$link}";
            }
        }

        // Store validation results
        update_option('llms_txt_generator_validation', array(
            'timestamp' => current_time('mysql'),
            'results' => $results
        ));

        return $results;
    }

    /**
     * Check if content has valid markdown structure
     */
    private function has_valid_structure($content) {
        // Check for main title
        if (!preg_match('/^# .+/m', $content)) {
            return false;
        }

        // Check for valid markdown links
        if (preg_match_all('/\[([^\]]+)\]\(([^\)]+)\)/', $content, $matches)) {
            foreach ($matches[2] as $url) {
                if (!filter_var($url, FILTER_VALIDATE_URL)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Validate all links in the content
     */
    private function validate_links($content) {
        $invalid_links = array();
        preg_match_all('/\[([^\]]+)\]\(([^\)]+)\)/', $content, $matches);

        if (!empty($matches[2])) {
            foreach ($matches[2] as $url) {
                // Skip external URLs
                if (!strpos($url, home_url()) === 0) {
                    continue;
                }

                // Remove home URL to get relative path
                $path = str_replace(home_url(), '', $url);
                
                // Check if URL exists in WordPress
                if (!$this->url_exists($path)) {
                    $invalid_links[] = $url;
                }
            }
        }

        return $invalid_links;
    }

    /**
     * Check if a URL exists in WordPress
     */
    private function url_exists($path) {
        global $wp_rewrite;

        // Remove leading slash
        $path = ltrim($path, '/');

        // Try to match against rewrite rules
        $matched = $wp_rewrite->wp_rewrite_rules();
        if (empty($matched)) {
            return true; // If no rewrite rules, assume URL is valid
        }

        foreach ($matched as $match => $query) {
            if (preg_match("#^{$match}#", $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the last validation results
     */
    public function get_last_validation_results() {
        $validation = get_option('llms_txt_generator_validation');
        if (!$validation) {
            return array(
                'timestamp' => null,
                'is_valid' => false,
                'errors' => array('No validation performed yet'),
                'warnings' => array()
            );
        }
        return $validation;
    }
} 