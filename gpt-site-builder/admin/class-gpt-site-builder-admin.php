<?php
/**
 * Клас за администраторския интерфейс на плъгина
 */
class GPT_Site_Builder_Admin {
    /**
     * Инстанция на GPT API класа
     */
    private $gpt_api;

    /**
     * Инициализиране на класа
     */
    public function __construct($gpt_api) {
        $this->gpt_api = $gpt_api;
    }

    /**
     * Добавяне на меню в админ панела
     */
    public function add_admin_menu() {
        add_menu_page(
            'GPT Site Builder',
            'GPT Site Builder',
            'manage_options',
            'gpt-site-builder',
            array($this, 'display_admin_page'),
            'dashicons-admin-customizer',
            30
        );

        add_submenu_page(
            'gpt-site-builder',
            'Настройки',
            'Настройки',
            'manage_options',
            'gpt-site-builder-settings',
            array($this, 'display_settings_page')
        );
    }

    /**
     * Зареждане на CSS стиловете
     */
    public function enqueue_styles($hook) {
        if (strpos($hook, 'gpt-site-builder') === false) {
            return;
        }

        wp_enqueue_style(
            'gpt-site-builder-admin',
            GSB_PLUGIN_URL . 'admin/css/gpt-site-builder-admin.css',
            array(),
            GSB_VERSION
        );
    }

    /**
     * Зареждане на JavaScript файловете
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'gpt-site-builder') === false) {
            return;
        }

        wp_enqueue_script(
            'gpt-site-builder-admin',
            GSB_PLUGIN_URL . 'admin/js/gpt-site-builder-admin.js',
            array('jquery'),
            GSB_VERSION,
            true
        );

        wp_localize_script(
            'gpt-site-builder-admin',
            'gpt_site_builder_params',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('gpt-site-builder-nonce')
            )
        );
    }

    /**
     * Показване на основната админ страница
     */
    public function display_admin_page() {
        // Проверка дали API ключът е конфигуриран
        $api_key = get_option('gpt_site_builder_api_key', '');
        if (empty($api_key)) {
            echo '<div class="notice notice-warning"><p>Моля, <a href="' . admin_url('admin.php?page=gpt-site-builder-settings') . '">конфигурирайте вашия GPT API ключ</a> преди да използвате плъгина.</p></div>';
        }

        // Зареждане на историята на разговора
        global $wpdb;
        $table_name = $wpdb->prefix . 'gpt_conversations';
        $conversations = $wpdb->get_results("SELECT * FROM $table_name ORDER BY time DESC LIMIT 10", ARRAY_A);
        ?>
        <div class="wrap gpt-site-builder">
            <h1>GPT Site Builder</h1>
            
            <div class="gpt-chat-container">
                <div class="gpt-chat-messages" id="gpt-chat-messages">
                    <?php if (empty($conversations)): ?>
                        <div class="gpt-message gpt-message-system">
                            <div class="gpt-message-content">
                                Здравейте! Аз съм вашият GPT асистент за изграждане на WordPress сайт. Как мога да ви помогна днес?
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach (array_reverse($conversations) as $conversation): ?>
                            <div class="gpt-message gpt-message-user">
                                <div class="gpt-message-content">
                                    <?php echo esc_html($conversation['user_message']); ?>
                                </div>
                                <div class="gpt-message-time">
                                    <?php echo date('d.m.Y H:i', strtotime($conversation['time'])); ?>
                                </div>
                            </div>
                            <div class="gpt-message gpt-message-gpt">
                                <div class="gpt-message-content">
                                    <?php echo wp_kses_post($conversation['gpt_response']); ?>
                                </div>
                                <?php if (!empty($conversation['actions_taken'])): ?>
                                    <div class="gpt-actions-taken">
                                        <h4>Извършени действия:</h4>
                                        <ul>
                                            <?php foreach (json_decode($conversation['actions_taken'], true) as $action): ?>
                                                <li>
                                                    <span class="<?php echo $action['status'] === 'success' ? 'success' : 'error'; ?>">
                                                        <?php echo esc_html($action['message']); ?>
                                                    </span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="gpt-chat-input">
                    <form id="gpt-message-form">
                        <textarea id="gpt-message-input" placeholder="Напишете вашето съобщение тук..."></textarea>
                        <button type="submit" class="button button-primary">Изпрати</button>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Показване на страницата с настройки
     */
    public function display_settings_page() {
        $api_key = get_option('gpt_site_builder_api_key', '');
        $model = get_option('gpt_site_builder_model', 'gpt-4');
        ?>
        <div class="wrap gpt-site-builder-settings">
            <h1>GPT Site Builder - Настройки</h1>
            
            <form id="gpt-settings-form">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="gpt-api-key">GPT API Ключ</label>
                        </th>
                        <td>
                            <input type="text" id="gpt-api-key" name="gpt_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" />
                            <p class="description">Въведете вашия OpenAI API ключ. Можете да го получите от <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI API Keys</a>.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="gpt-model">GPT Модел</label>
                        </th>
                        <td>
                            <select id="gpt-model" name="gpt_model">
                                <option value="gpt-4" <?php selected($model, 'gpt-4'); ?>>GPT-4</option>
                                <option value="gpt-4-turbo" <?php selected($model, 'gpt-4-turbo'); ?>>GPT-4 Turbo</option>
                                <option value="gpt-3.5-turbo" <?php selected($model, 'gpt-3.5-turbo'); ?>>GPT-3.5 Turbo</option>
                            </select>
                            <p class="description">Изберете GPT модела, който искате да използвате.</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary">Запази настройките</button>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Обработка на AJAX заявка за изпращане на съобщение
     */
    public function handle_ajax_send_message() {
        check_ajax_referer('gpt-site-builder-nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Нямате достатъчно права.'));
            return;
        }

        $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';

        if (empty($message)) {
            wp_send_json_error(array('message' => 'Съобщението не може да бъде празно.'));
            return;
        }

        // Зареждане на историята на разговора
        global $wpdb;
        $table_name = $wpdb->prefix . 'gpt_conversations';
        $conversation_history = array();
        $conversations = $wpdb->get_results("SELECT * FROM $table_name ORDER BY time DESC LIMIT 5", ARRAY_A);

        foreach (array_reverse($conversations) as $conversation) {
            $conversation_history[] = array(
                'role' => 'user',
                'content' => $conversation['user_message']
            );
            $conversation_history[] = array(
                'role' => 'assistant',
                'content' => $conversation['gpt_response']
            );
        }

        // Изпращане на съобщението към GPT API
        $response = $this->gpt_api->send_message($message, $conversation_history);

        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => $response->get_error_message()));
            return;
        }

        $gpt_response = $response['choices'][0]['message']['content'];

        // Анализиране на отговора за действия
        $actions = $this->gpt_api->parse_actions($gpt_response);
        $results = array();

        if (!empty($actions)) {
            $results = $this->gpt_api->execute_actions($actions);
            
            // Премахване на командите от отговора
            foreach ($actions as $action) {
                if ($action['type'] === 'create_page') {
                    $gpt_response = str_replace("[CREATE_PAGE:{$action['title']}:{$action['content']}]", '', $gpt_response);
                }
            }
        }

        // Запазване на разговора в базата данни
        $wpdb->insert(
            $table_name,
            array(
                'time' => current_time('mysql'),
                'user_message' => $message,
                'gpt_response' => $gpt_response,
                'actions_taken' => !empty($results) ? json_encode($results) : null
            )
        );

        wp_send_json_success(array(
            'message' => $gpt_response,
            'actions' => $results
        ));
    }

    /**
     * Обработка на AJAX заявка за запазване на настройките
     */
    public function handle_ajax_save_settings() {
        check_ajax_referer('gpt-site-builder-nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Нямате достатъчно права.'));
            return;
        }

        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        $model = isset($_POST['model']) ? sanitize_text_field($_POST['model']) : 'gpt-4';

        if (empty($api_key)) {
            wp_send_json_error(array('message' => 'API ключът е задължителен.'));
            return;
        }

        $result = $this->gpt_api->update_settings($api_key, $model);

        if ($result) {
            wp_send_json_success(array('message' => 'Настройките бяха запазени успешно.'));
        } else {
            wp_send_json_error(array('message' => 'Грешка при запазване на настройките.'));
        }
    }
}