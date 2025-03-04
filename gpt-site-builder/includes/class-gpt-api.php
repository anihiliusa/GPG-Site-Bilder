<?php
/**
 * Клас за комуникация с GPT API
 */
class GPT_API {
    /**
     * API ключ за GPT
     */
    private $api_key;

    /**
     * Модел на GPT за използване
     */
    private $model;

    /**
     * Инициализиране на класа
     */
    public function __construct() {
        $this->api_key = get_option('gpt_site_builder_api_key', '');
        $this->model = get_option('gpt_site_builder_model', 'gpt-4');
    }

    /**
     * Изпращане на съобщение към GPT API
     *
     * @param string $message Съобщението за изпращане
     * @param array $conversation_history История на разговора
     * @return array|WP_Error Отговор от API или грешка
     */
    public function send_message($message, $conversation_history = array()) {
        if (empty($this->api_key)) {
            return new WP_Error('missing_api_key', 'API ключът не е конфигуриран.');
        }

        $url = 'https://api.openai.com/v1/chat/completions';

        $messages = array();
        
        // Добавяне на системно съобщение
        $messages[] = array(
            'role' => 'system',
            'content' => 'Ти си експертен асистент за изграждане на WordPress сайтове. Помагаш на потребителя да създаде и модифицира WordPress сайт чрез чат комуникация. Можеш да създаваш страници, публикации, меню, да инсталираш и конфигурираш теми и плъгини, и да извършваш други задачи по изграждането на сайта.'
        );

        // Добавяне на историята на разговора
        foreach ($conversation_history as $entry) {
            $messages[] = array(
                'role' => $entry['role'],
                'content' => $entry['content']
            );
        }

        // Добавяне на текущото съобщение
        $messages[] = array(
            'role' => 'user',
            'content' => $message
        );

        $body = array(
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 2000
        );

        $args = array(
            'body' => json_encode($body),
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key
            ),
            'timeout' => 60
        );

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            return new WP_Error('api_error', $body['error']['message']);
        }

        return $body;
    }

    /**
     * Анализиране на отговора от GPT за извличане на действия
     *
     * @param string $response Отговор от GPT
     * @return array Масив с действия за изпълнение
     */
    public function parse_actions($response) {
        // Тук ще имплементираме логика за извличане на действия от отговора
        // Например, ако GPT отговори с команда за създаване на страница, ще я разпознаем и изпълним
        
        $actions = array();
        
        // Примерна логика за разпознаване на действия
        if (strpos($response, '[CREATE_PAGE:') !== false) {
            preg_match_all('/\[CREATE_PAGE:(.*?):(.*?)\]/', $response, $matches, PREG_SET_ORDER);
            
            foreach ($matches as $match) {
                $title = trim($match[1]);
                $content = trim($match[2]);
                
                $actions[] = array(
                    'type' => 'create_page',
                    'title' => $title,
                    'content' => $content
                );
            }
        }
        
        // Можем да добавим повече логика за разпознаване на други действия
        
        return $actions;
    }

    /**
     * Изпълнение на действия върху WordPress сайта
     *
     * @param array $actions Масив с действия за изпълнение
     * @return array Резултати от изпълнението
     */
    public function execute_actions($actions) {
        $results = array();
        
        foreach ($actions as $action) {
            switch ($action['type']) {
                case 'create_page':
                    $page_id = wp_insert_post(array(
                        'post_title' => $action['title'],
                        'post_content' => $action['content'],
                        'post_status' => 'publish',
                        'post_type' => 'page'
                    ));
                    
                    if ($page_id) {
                        $results[] = array(
                            'status' => 'success',
                            'message' => 'Страницата "' . $action['title'] . '" беше създадена успешно.',
                            'data' => array('page_id' => $page_id)
                        );
                    } else {
                        $results[] = array(
                            'status' => 'error',
                            'message' => 'Грешка при създаване на страницата "' . $action['title'] . '".'
                        );
                    }
                    break;
                
                // Можем да добавим повече случаи за други типове действия
                
                default:
                    $results[] = array(
                        'status' => 'error',
                        'message' => 'Неизвестен тип действие: ' . $action['type']
                    );
                    break;
            }
        }
        
        return $results;
    }

    /**
     * Обновяване на настройките на API
     *
     * @param string $api_key Нов API ключ
     * @param string $model Нов модел
     * @return bool Успех или неуспех
     */
    public function update_settings($api_key, $model) {
        $this->api_key = $api_key;
        $this->model = $model;
        
        update_option('gpt_site_builder_api_key', $api_key);
        update_option('gpt_site_builder_model', $model);
        
        return true;
    }
}