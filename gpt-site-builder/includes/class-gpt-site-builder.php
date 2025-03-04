<?php
/**
 * Основен клас на плъгина
 */
class GPT_Site_Builder {
    /**
     * Инстанция на GPT API клас
     */
    protected $gpt_api;

    /**
     * Инстанция на админ класа
     */
    protected $admin;

    /**
     * Инициализиране на плъгина
     */
    public function __construct() {
        $this->load_dependencies();
        $this->define_admin_hooks();
    }

    /**
     * Зареждане на зависимостите
     */
    private function load_dependencies() {
        $this->gpt_api = new GPT_API();
        $this->admin = new GPT_Site_Builder_Admin($this->gpt_api);
    }

    /**
     * Дефиниране на админ куките
     */
    private function define_admin_hooks() {
        add_action('admin_menu', array($this->admin, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this->admin, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($this->admin, 'enqueue_scripts'));
        add_action('wp_ajax_gpt_site_builder_send_message', array($this->admin, 'handle_ajax_send_message'));
        add_action('wp_ajax_gpt_site_builder_save_settings', array($this->admin, 'handle_ajax_save_settings'));
    }

    /**
     * Стартиране на плъгина
     */
    public function run() {
        // Стартиране на плъгина
    }
}