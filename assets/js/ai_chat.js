(function () {
    const config = window.aiChatConfig || { isLoggedIn: false, csrfToken: '', apiEndpoint: 'api/landing_helper.php', actionEndpoint: '' };

    let ui = {};
    let chatHistory = JSON.parse(sessionStorage.getItem('PROJECTM_AI_CHAT')) || [];

    function safeFocus() {
        const isTouchDevice = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0) || (window.innerWidth <= 768);
        if (!isTouchDevice && ui.input) {
            ui.input.focus();
        }
    }

    function clearChatHistory() {
        if (confirm("Are you sure you want to clear your chat history?")) {
            sessionStorage.removeItem('PROJECTM_AI_CHAT');
            chatHistory = [];
            ui.messages.innerHTML = '';
            const welcomeMsg = "Hello! 👋 I am ZNODA AI, your premium personal finance welcoming assistant. Ask me anything about Money Management features or something else!";
            appendBubble('bot', welcomeMsg);
            chatHistory = [{ role: 'bot', text: welcomeMsg }];
            sessionStorage.setItem('PROJECTM_AI_CHAT', JSON.stringify(chatHistory));
        }
    }

    function init() {
        ui = {
            bubble: document.getElementById('aiChatBubble'),
            window: document.getElementById('aiChatWindow'),
            closeBtn: document.getElementById('aiChatClose'),
            clearBtn: document.getElementById('aiChatClear'),
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

        if (ui.clearBtn) {
            ui.clearBtn.addEventListener('click', clearChatHistory);
        }

        ui.pills.forEach(pill => {
            pill.addEventListener('click', () => {
                ui.input.value = pill.textContent.trim();
                handleSend();
            });
        });

        if (chatHistory.length === 0) {
            const welcomeMsg = "Hello! 👋 I am ZNODA AI, your premium personal finance welcoming assistant. Ask me anything about Money Management features or security!";
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
            safeFocus();
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

    function parseMarkdown(text) {
        let html = text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
        html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        const lines = html.split('\n');
        let inList = false;
        let processedLines = [];
        lines.forEach(line => {
            const trimmed = line.trim();
            if (trimmed.startsWith('* ') || trimmed.startsWith('- ')) {
                if (!inList) {
                    processedLines.push('<ul class="ai-chat-list" style="margin: 4px 0; padding-left: 20px; list-style-type: disc;">');
                    inList = true;
                }
                const content = trimmed.substring(2);
                processedLines.push(`<li style="margin-bottom: 2px;">${content}</li>`);
            } else {
                if (inList) {
                    processedLines.push('</ul>');
                    inList = false;
                }
                processedLines.push(line);
            }
        });
        if (inList) {
            processedLines.push('</ul>');
        }
        html = processedLines.join('\n');
        html = html.replace(/\n\n/g, '<div style="margin-bottom: 8px;"></div>');
        html = html.replace(/\n/g, '<br>');
        return html;
    }

    function appendBubble(role, text) {
        const msgDiv = document.createElement('div');
        msgDiv.className = `ai-msg ${role === 'user' ? 'user' : 'bot'}`;

        const bubble = document.createElement('div');
        bubble.className = 'ai-msg-bubble';

        if (role === 'bot') {
            bubble.innerHTML = parseMarkdown(text);
        } else {
            bubble.textContent = text;
        }

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
        if (ui.input.disabled) return;
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
                history: chatHistory.slice(0, -1).slice(-10)
            })
        })
            .then(res => {
                if (!res.ok) {
                    return res.text().then(text => {
                        throw new Error(`HTTP ${res.status}: ${text}`);
                    });
                }
                return res.json();
            })
            .then(data => {
                hideTyping();
                ui.input.disabled = false;
                ui.sendBtn.disabled = false;
                safeFocus();

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
                appendMessage('bot', 'Failed to connect to AI assistant. Please try again later.');
            });
    }

    document.addEventListener('DOMContentLoaded', init);
})();
