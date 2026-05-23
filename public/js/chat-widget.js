(function () {
    'use strict';

    var cfg = window.PC_SHOP_CHAT || {};
    var apiUrl = cfg.apiUrl || '/api/chat';
    var quickActions = Array.isArray(cfg.quickActions) ? cfg.quickActions : [];
    var shopName = cfg.shopName || 'PC Parts Shop';
    var typewriterMax = typeof cfg.typewriterMaxChars === 'number' ? cfg.typewriterMaxChars : 900;
    var typewriterMs = typeof cfg.typewriterMsPerChar === 'number' ? cfg.typewriterMsPerChar : 10;

    var root = document.getElementById('pc-chat-root');
    if (!root) {
        return;
    }

    var launcher = root.querySelector('.pc-chat-launcher');
    var panel = root.querySelector('.pc-chat-panel');
    var closeBtn = root.querySelector('.pc-chat-close');
    var messagesEl = root.querySelector('.pc-chat-messages');
    var quickEl = root.querySelector('.pc-chat-quick');
    var inputEl = root.querySelector('.pc-chat-input');
    var sendBtn = root.querySelector('.pc-chat-send');

    if (!launcher || !panel || !messagesEl || !quickEl || !inputEl || !sendBtn) {
        return;
    }

    var sessionId = localStorage.getItem('pc_chat_session_id') || '';
    var isOpen = false;
    var isSending = false;
    var welcomed = sessionStorage.getItem('pc_chat_welcomed') === '1';
    var typewriterTimer = null;

    function genId() {
        if (window.crypto && crypto.randomUUID) {
            return crypto.randomUUID();
        }
        return 's-' + Date.now() + '-' + Math.random().toString(16).slice(2);
    }

    if (!sessionId) {
        sessionId = genId();
        localStorage.setItem('pc_chat_session_id', sessionId);
    }

    function prefersReducedMotion() {
        return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    function scrollToBottom() {
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function clearTypewriter() {
        if (typewriterTimer !== null) {
            clearTimeout(typewriterTimer);
            typewriterTimer = null;
        }
    }

    /** Tin nhắn user: hiển thị ngay */
    function appendMessage(text, role) {
        var div = document.createElement('div');
        div.className = 'pc-chat-msg pc-chat-msg--' + (role === 'user' ? 'user' : 'bot');
        div.setAttribute('role', 'article');
        div.textContent = text;
        messagesEl.appendChild(div);
        scrollToBottom();
        return div;
    }

    /**
     * Tin nhắn bot: hiệu ứng gõ (typewriter) nếu ngắn; dài thì hiện ngay.
     */
    function appendBotMessageTypewriter(text) {
        clearTypewriter();
        var div = document.createElement('div');
        div.className = 'pc-chat-msg pc-chat-msg--bot';
        div.setAttribute('role', 'article');
        messagesEl.appendChild(div);
        scrollToBottom();

        if (prefersReducedMotion() || text.length > typewriterMax) {
            div.textContent = text;
            scrollToBottom();
            return div;
        }

        var i = 0;
        function tick() {
            div.textContent = text.slice(0, i);
            scrollToBottom();
            i += 1;
            if (i <= text.length) {
                var delay = typewriterMs;
                if (text.charAt(i - 1) === '\n') {
                    delay = Math.min(120, typewriterMs * 4);
                }
                typewriterTimer = window.setTimeout(tick, delay);
            } else {
                typewriterTimer = null;
            }
        }
        tick();
        return div;
    }

    function showTyping() {
        var div = document.createElement('div');
        div.className = 'pc-chat-msg pc-chat-msg--typing';
        div.setAttribute('data-typing', '1');
        div.innerHTML =
            '<div class="pc-chat-typing-row">' +
            '<div class="pc-chat-typing-dots" aria-hidden="true">' +
            '<span></span><span></span><span></span></div>' +
            '<span class="pc-chat-typing-label">Đang soạn tin…</span>' +
            '</div>';
        messagesEl.appendChild(div);
        scrollToBottom();
        return div;
    }

    function removeTyping() {
        var el = messagesEl.querySelector('[data-typing="1"]');
        if (el) {
            el.remove();
        }
    }

    function setOpen(open) {
        isOpen = open;
        panel.classList.toggle('is-open', open);
        if (open) {
            panel.removeAttribute('hidden');
        } else {
            panel.setAttribute('hidden', '');
        }
        panel.setAttribute('aria-hidden', open ? 'false' : 'true');
        launcher.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) {
            inputEl.focus();
            if (!welcomed) {
                welcomed = true;
                sessionStorage.setItem('pc_chat_welcomed', '1');
                appendBotMessageTypewriter(
                    'Xin chào! Tôi là trợ lý ' +
                        shopName +
                        '. Tôi có thể tư vấn linh kiện, khuyến mãi và hướng dẫn mua hàng online. Bạn cần hỗ trợ gì?'
                );
            }
        } else {
            clearTypewriter();
        }
    }

    function renderQuickActions() {
        quickEl.innerHTML = '';
        var labels = ['Khuyến mãi', 'Mua hàng online', 'Tư vấn linh kiện', 'Build PC'];
        quickActions.forEach(function (action, index) {
            if (index > 0) {
                var sep = document.createElement('span');
                sep.className = 'pc-chat-quick-sep';
                sep.setAttribute('aria-hidden', 'true');
                sep.textContent = '·';
                quickEl.appendChild(sep);
            }
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'pc-chat-quick-btn pc-chat-interactive';
            var label = action.label || labels[index] || action.id;
            btn.textContent = label;
            btn.addEventListener('click', function () {
                sendMessage(action.message || label, action.id || '');
            });
            quickEl.appendChild(btn);
        });
    }

    function setSending(sending) {
        isSending = sending;
        sendBtn.disabled = sending;
        inputEl.disabled = sending;
    }

    function sendMessage(text, quickActionId) {
        var message = (text || '').trim();
        if (!message || isSending) {
            return;
        }

        appendMessage(message, 'user');
        inputEl.value = '';
        setSending(true);
        var typingEl = showTyping();

        fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json; charset=utf-8',
                Accept: 'application/json',
            },
            body: JSON.stringify({
                message: message,
                sessionId: sessionId,
                pageUrl: window.location.href,
                pagePath: window.location.pathname + window.location.search,
                quickActionId: quickActionId || '',
            }),
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    return { ok: res.ok, data: data };
                });
            })
            .then(function (result) {
                removeTyping();
                if (result.data && result.data.sessionId) {
                    sessionId = result.data.sessionId;
                    localStorage.setItem('pc_chat_session_id', sessionId);
                }
                if (result.ok && result.data && result.data.reply) {
                    appendBotMessageTypewriter(String(result.data.reply));
                } else {
                    var err =
                        (result.data && result.data.error) ||
                        'Không kết nối được trợ lý. Vui lòng thử lại hoặc gọi hotline.';
                    appendBotMessageTypewriter(err);
                }
            })
            .catch(function () {
                removeTyping();
                appendBotMessageTypewriter(
                    'Lỗi mạng. Vui lòng kiểm tra kết nối và thử lại, hoặc liên hệ hotline trên trang.'
                );
            })
            .finally(function () {
                setSending(false);
                if (typingEl && typingEl.parentNode) {
                    typingEl.remove();
                }
            });
    }

    launcher.addEventListener('click', function () {
        setOpen(!isOpen);
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            setOpen(false);
        });
    }

    sendBtn.addEventListener('click', function () {
        sendMessage(inputEl.value, '');
    });

    inputEl.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage(inputEl.value, '');
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && isOpen) {
            setOpen(false);
        }
    });

    renderQuickActions();
})();
