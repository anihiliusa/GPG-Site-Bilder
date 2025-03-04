/**
 * JavaScript за админ панела на GPT Site Builder
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Референции към DOM елементите
        const $chatMessages = $('#gpt-chat-messages');
        const $messageForm = $('#gpt-message-form');
        const $messageInput = $('#gpt-message-input');
        const $settingsForm = $('#gpt-settings-form');
        
        // Превъртане до най-новото съобщение
        if ($chatMessages.length) {
            $chatMessages.scrollTop($chatMessages[0].scrollHeight);
        }
        
        // Обработка на изпращане на съобщение
        if ($messageForm.length) {
            $messageForm.on('submit', function(e) {
                e.preventDefault();
                
                const message = $messageInput.val().trim();
                
                if (!message) {
                    return;
                }
                
                // Добавяне на съобщението на потребителя към чата
                $chatMessages.append(`
                    <div class="gpt-message gpt-message-user">
                        <div class="gpt-message-content">${escapeHtml(message)}</div>
                        <div class="gpt-message-time">${getCurrentTime()}</div>
                    </div>
                `);
                
                // Добавяне на индикатор за зареждане
                const $loadingMessage = $(`
                    <div class="gpt-message gpt-message-gpt" id="gpt-loading-message">
                        <div class="gpt-message-content">
                            <span class="gpt-loading"></span> Мисля...
                        </div>
                    </div>
                `);
                
                $chatMessages.append($loadingMessage);
                $chatMessages.scrollTop($chatMessages[0].scrollHeight);
                
                // Изчистване на полето за въвеждане
                $messageInput.val('');
                
                // Изпращане на AJAX заявка
                $.ajax({
                    url: gpt_site_builder_params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'gpt_site_builder_send_message',
                        nonce: gpt_site_builder_params.nonce,
                        message: message
                    },
                    success: function(response) {
                        // Премахване на индикатора за зареждане
                        $('#gpt-loading-message').remove();
                        
                        if (response.success) {
                            // Добавяне на отговора от GPT
                            let gptMessageHtml = `
                                <div class="gpt-message gpt-message-gpt">
                                    <div class="gpt-message-content">${response.data.message}</div>
                            `;
                            
                            // Добавяне на информация за извършените действия, ако има такива
                            if (response.data.actions && response.data.actions.length > 0) {
                                gptMessageHtml += `
                                    <div class="gpt-actions-taken">
                                        <h4>Извършени действия:</h4>
                                        <ul>
                                `;
                                
                                response.data.actions.forEach(function(action) {
                                    gptMessageHtml += `
                                        <li>
                                            <span class="${action.status === 'success' ? 'success' : 'error'}">
                                                ${escapeHtml(action.message)}
                                            </span>
                                        </li>
                                    `;
                                });
                                
                                gptMessageHtml += `
                                        </ul>
                                    </div>
                                `;
                            }
                            
                            gptMessageHtml += `</div>`;
                            
                            $chatMessages.append(gptMessageHtml);
                        } else {
                            // Показване на съобщение за грешка
                            $chatMessages.append(`
                                <div class="gpt-message gpt-message-gpt">
                                    <div class="gpt-message-content">
                                        <span style="color: #F44336;">Грешка: ${response.data.message}</span>
                                    </div>
                                </div>
                            `);
                        }
                        
                        // Превъртане до най-новото съобщение
                        $chatMessages.scrollTop($chatMessages[0].scrollHeight);
                    },
                    error: function() {
                        // Премахване на индикатора за зареждане
                        $('#gpt-loading-message').remove();
                        
                        // Показване на съобщение за грешка
                        $chatMessages.append(`
                            <div class="gpt-message gpt-message-gpt">
                                <div class="gpt-message-content">
                                    <span style="color: #F44336;">Възникна грешка при комуникацията със сървъра. Моля, опитайте отново.</span>
                                </div>
                            </div>
                        `);
                        
                        // Превъртане до най-новото съобщение
                        $chatMessages.scrollTop($chatMessages[0].scrollHeight);
                    }
                });
            });
        }
        
        // Обработка на запазване на настройките
        if ($settingsForm.length) {
            $settingsForm.on('submit', function(e) {
                e.preventDefault();
                
                const apiKey = $('#gpt-api-key').val().trim();
                const model = $('#gpt-model').val();
                
                if (!apiKey) {
                    alert('Моля, въведете вашия GPT API ключ.');
                    return;
                }
                
                // Показване на индикатор за зареждане
                const $submitButton = $(this).find('button[type="submit"]');
                const originalButtonText = $submitButton.text();
                $submitButton.html('<span class="gpt-loading"></span> Запазване...');
                $submitButton.prop('disabled', true);
                
                // Изпращане на AJAX заявка
                $.ajax({
                    url: gpt_site_builder_params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'gpt_site_builder_save_settings',
                        nonce: gpt_site_builder_params.nonce,
                        api_key: apiKey,
                        model: model
                    },
                    success: function(response) {
                        // Възстановяване на бутона
                        $submitButton.text(originalButtonText);
                        $submitButton.prop('disabled', false);
                        
                        if (response.success) {
                            // Показване на съобщение за успех
                            alert(response.data.message);
                        } else {
                            // Показване на съобщение за грешка
                            alert('Грешка: ' + response.data.message);
                        }
                    },
                    error: function() {
                        // Възстановяване на бутона
                        $submitButton.text(originalButtonText);
                        $submitButton.prop('disabled', false);
                        
                        // Показване на съобщение за грешка
                        alert('Възникна грешка при комуникацията със сървъра. Моля, опитайте отново.');
                    }
                });
            });
        }
        
        // Помощни функции
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function getCurrentTime() {
            const now = new Date();
            const day = String(now.getDate()).padStart(2, '0');
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const year = now.getFullYear();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            
            return `${day}.${month}.${year} ${hours}:${minutes}`;
        }
    });
})(jQuery);