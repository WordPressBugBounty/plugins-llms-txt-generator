<?php

if (!defined('ABSPATH')) {
    exit;
}

class LLMS_TXT_GENERATOR_Cache {
    private static $instance = null;
    private $cache_group = 'llms_txt_generator';
    private $default_expiration = 3600; 

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    /**
     * Get cache item
     */
    public function get($key) {
        return get_transient($this->get_cache_key($key));
    }

    /**
     * Set cache item
     */
    public function set($key, $value, $expiration = null) {
        if (null === $expiration) {
            $expiration = $this->default_expiration;
        }
        return set_transient($this->get_cache_key($key), $value, $expiration);
    }

    /**
     * Remove cache item
     */
    public function delete($key) {
        return delete_transient($this->get_cache_key($key));
    }

    /**
     * Clear all cache
     */
    public function clear_all() {
        $all_options = wp_load_alloptions();
        $prefix = '_transient_' . $this->cache_group;
        $timeout_prefix = '_transient_timeout_' . $this->cache_group;
        $deleted = 0;

        foreach ($all_options as $option => $value) {
            if (strpos($option, $prefix) === 0 || strpos($option, $timeout_prefix) === 0) {
                if (delete_option($option)) {
                    $deleted++;
                }
            }
        }
        
        return $deleted;
    }

    /**
     * Get cache key
     */
    private function get_cache_key($key) {
        return $this->cache_group . '_' . $key;
    }

    /**
     * Check if cache is enabled
     */
    public function is_enabled() {
        return true;
    }

    /**
     * Get cache stats
     */
    public function get_stats() {
        $stats = array(
            'total_items' => 0,
            'size' => 0
        );

        $all_options = wp_load_alloptions();
        $prefix = '_transient_' . $this->cache_group;
        
        foreach ($all_options as $option => $value) {
            if (strpos($option, $prefix) === 0) {
                $stats['total_items']++;
                $stats['size'] += strlen($value);
            }
        }

        return $stats;
    }
} 