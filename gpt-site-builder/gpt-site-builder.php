<?php
/**
 * Plugin Name: GPT Site Builder
 * Plugin URI: https://example.com/gpt-site-builder
 * Description: WordPress плъгин, който използва GPT API за изграждане на сайт чрез чат комуникация в админ панела.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: gpt-site-builder
 * Domain Path: /languages
 */

// Предотвратяване на директен достъп
if (!defined('ABSPATH')) {
    exit;
}

// Дефиниране на константи
define('GSB_VERSION', '1.0.0');
define('GSB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GSB_PLUGIN_URL', plugin_dir_url(__FILE__));

// Включване на необходимите файлове
require_once GSB_PLUGIN_DIR . 'includes/class-gpt-site-builder.php';
require_once GSB_PLUGIN_DIR . 'includes/class-gpt-api.php';
require_once GSB_PLUGIN_DIR . 'admin/class-gpt-site-builder-admin.php';

// Инициализиране на плъгина
function gpt_site_builder_init() {
    $plugin = new GPT_Site_Builder();
    $plugin->run();
}
gpt_site_builder_init();

// Активиране на плъгина
register_activation_hook(__FILE__, 'gpt_site_builder_activate');
function gpt_site_builder_activate() {
    // Създаване на необходимите таблици в базата данни
    global $wpdb;
    $table_name = $wpdb->prefix . 'gpt_conversations';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        user_message text NOT NULL,
        gpt_response text NOT NULL,
        actions_taken text,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Добавяне на опции по подразбиране
    add_option('gpt_site_builder_api_key', '');
    add_option('gpt_site_builder_model', 'gpt-4');
}

// Деактивиране на плъгина
register_deactivation_hook(__FILE__, 'gpt_site_builder_deactivate');
function gpt_site_builder_deactivate() {
    // Почистване при деактивиране
}