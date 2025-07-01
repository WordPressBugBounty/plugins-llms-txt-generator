<?php

if (!defined('ABSPATH')) {
    exit;
}

class LLMS_TXT_GENERATOR_Access_Control {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Inicialização básica
    }

    /**
     * Verifica se o usuário tem permissão para acessar uma funcionalidade específica
     */
    public function can_access($feature) {
        // Verifica se o usuário tem permissões básicas do WordPress
        if (!current_user_can('manage_options')) {
            return false;
        }

        // Lista de features básicas permitidas
        $allowed_features = array(
            'settings',
            'dashboard',
            'basic_cache',
            'generate_file'
        );

        return in_array($feature, $allowed_features);
    }

    /**
     * Verifica e bloqueia acesso se necessário
     */
    public function check_access($feature) {
        if (!$this->can_access($feature)) {
            if (wp_doing_ajax()) {
                wp_send_json_error('Access denied. This feature requires administrator permissions.');
                exit;
            } else {
                wp_die('You do not have permission to access this feature.');
            }
        }
        return true;
    }

    /**
     * Verifica se uma feature está disponível
     */
    public function is_feature_available($feature) {
        $allowed_features = array(
            'settings',
            'dashboard',
            'basic_cache',
            'generate_file'
        );

        return in_array($feature, $allowed_features);
    }
} 