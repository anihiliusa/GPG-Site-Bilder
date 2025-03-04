<?php
/**
 * Uninstall GPT Site Builder
 *
 * @package GPT_Site_Builder
 */

// Ако uninstall.php не е извикан от WordPress, излизаме
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Изтриване на опциите
delete_option('gpt_site_builder_api_key');
delete_option('gpt_site_builder_model');

// Изтриване на таблицата с разговори
global $wpdb;
$table_name = $wpdb->prefix . 'gpt_conversations';
$wpdb->query("DROP TABLE IF EXISTS $table_name");