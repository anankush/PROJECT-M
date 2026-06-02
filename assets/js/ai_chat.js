(function () {
    const config = window.aiChatConfig || { isLoggedIn: false, csrfToken: '', apiEndpoint: 'api/ai_chat.php', actionEndpoint: '' };
    
    let ui = {};
    let chatHistory = JSON.parse(sessionStorage.getItem('PROJECTM_AI_CHAT')) || [];

    function init() {
        ui = {
            bubble: document.getElementById('aiChatBubble'),
            window: document.getElementById('aiChatWindow'),
            closeBtn: document.getElementById('aiChatClose'),
            messages: document.getElementById('aiChatMessages'),
            input: document.getElementById('aiMessageInput'),
            sendBtn: document.getElementById('aiSendBtn'),
            pills: document.querySelectorAll('.ai-pill')
        };

        if (!ui.bubble || !ui.window) return;

        ui.bubble.addEventListener('click', toggleChat);
        ui.closeBtn.addEventListener('click', toggleChat);
        ui.sendBtn.addEventListener('click', handleSend);
        ui.input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') handleSend();
        });

        ui.pills.forEach(pill => {
            pill.addEventListener('click', () => {
                ui.input.value = pill.textContent.trim();
                handleSend();
            });
        });

        if (chatHistory.length === 0) {
            const welcomeMsg = "Hello! Welcome to Money Management. I am your assistant. Ask me anything about how the app works!";
            appendMessage('bot', welcomeMsg);
        } else {
            chatHistory.forEach(msg => appendBubble(msg.role, msg.text));
            scrollToBottom();
        }
    }

    function toggleChat() {
        ui.window.classList.toggle('active');
        if (ui.window.classList.contains('active')) {
            document.body.classList.add('ai-chat-open-state');
            ui.input.focus();
            scrollToBottom();
        } else {
            document.body.classList.remove('ai-chat-open-state');
        }
    }

    function appendMessage(role, text) {
        appendBubble(role, text);
        chatHistory.push({ role, text });
        sessionStorage.setItem('PROJECTM_AI_CHAT', JSON.stringify(chatHistory));
        scrollToBottom();
    }

    function appendBubble(role, text) {
        const msgDiv = document.createElement('div');
        msgDiv.className = `ai-msg ${role === 'user' ? 'user' : 'bot'}`;
        
        const bubble = document.createElement('div');
        bubble.className = 'ai-msg-bubble';
        bubble.textContent = text;
        
        msgDiv.appendChild(bubble);
        ui.messages.appendChild(msgDiv);
    }

    function scrollToBottom() {
        ui.messages.scrollTop = ui.messages.scrollHeight;
    }

    function showTyping() {
        const ind = document.createElement('div');
        ind.id = 'aiTypingIndicator';
        ind.className = 'ai-msg bot';
        ind.innerHTML = `
            <div class="ai-msg-bubble">
                <div class="ai-typing-indicator">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        `;
        ui.messages.appendChild(ind);
        scrollToBottom();
    }

    function hideTyping() {
        const ind = document.getElementById('aiTypingIndicator');
        if (ind) ind.remove();
    }

    function handleSend() {
        const text = ui.input.value.trim();
        if (!text) return;

        appendMessage('user', text);
        ui.input.value = '';
        ui.input.disabled = true;
        ui.sendBtn.disabled = true;

        showTyping();

        fetch(config.apiEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                message: text,
                history: chatHistory.slice(-10)
            })
        })
        .then(res => res.json())
        .then(data => {
            hideTyping();
            ui.input.disabled = false;
            ui.sendBtn.disabled = false;
            ui.input.focus();

            if (data.status === 'success') {
                appendMessage('bot', data.reply);
            } else {
                appendMessage('bot', data.reply || 'An error occurred.');
            }
        })
        .catch(err => {
            console.error('[AI Chat Connection Error]:', err);
            hideTyping();
            ui.input.disabled = false;
            ui.sendBtn.disabled = false;
            appendMessage('bot', 'Failed to connect to AI assistant.');
        });
    }

    document.addEventListener('DOMContentLoaded', init);
})();
