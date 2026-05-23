<?php

$chatConfigPath = dirname(__DIR__, 3) . '/config/chat.php';
$chatConfig = is_file($chatConfigPath) ? (require $chatConfigPath) : [];
if (!is_array($chatConfig)) {
    $chatConfig = [];
}

if (empty($chatConfig['enabled'])) {
    return;
}

$chatQuickActions = $chatConfig['quick_actions'] ?? [];
$chatShop = $chatConfig['shop'] ?? [];
$chatCss = chat_widget_css();
$chatJs = chat_widget_js();
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,400;0,500;0,600;0,700&display=swap" rel="stylesheet">
<style id="pc-chat-widget-css"><?= $chatCss ?></style>
<div id="pc-chat-root" aria-live="polite">
    <div class="pc-chat-panel pc-chat-interactive" id="pc-chat-panel" role="dialog" aria-labelledby="pc-chat-title" aria-hidden="true" hidden>
        <header class="pc-chat-header">
            <div class="pc-chat-header-top">
                <div class="pc-chat-avatar-wrap">
                    <div class="pc-chat-avatar" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                            <path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 0 1 7 7v1.27A2 2 0 0 1 22 17.27V19a2 2 0 0 1-2 2h-1.09A6 6 0 0 1 13 23h-2a6 6 0 0 1-5.91-2H4a2 2 0 0 1-2-2v-1.73A2 2 0 0 1 2.09 15 15 15 0 0 1 2 14V9a7 7 0 0 1 7-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 0 1 2-2z"/>
                            <circle cx="9" cy="13" r="1" fill="currentColor" stroke="none"/>
                            <circle cx="15" cy="13" r="1" fill="currentColor" stroke="none"/>
                        </svg>
                    </div>
                    <span class="pc-chat-avatar-online" aria-hidden="true"></span>
                </div>
                <div class="pc-chat-header-text">
                    <h2 class="pc-chat-title" id="pc-chat-title">Trợ lý <?= htmlspecialchars((string) ($chatShop['name'] ?? 'PC Parts Shop'), ENT_QUOTES, 'UTF-8') ?></h2>
                    <div class="pc-chat-status-row">
                        <span class="pc-chat-status-dot" aria-hidden="true"></span>
                        <span class="pc-chat-status-text">Đang trực tuyến</span>
                    </div>
                    <p class="pc-chat-subtitle">Tư vấn · Khuyến mãi · Mua hàng online</p>
                </div>
                <button type="button" class="pc-chat-close pc-chat-interactive" aria-label="Đóng cửa sổ chat">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false">
                        <path d="M18 6L6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </header>
        <div class="pc-chat-messages pc-chat-interactive" id="pc-chat-messages" role="log" aria-relevant="additions"></div>
        <div class="pc-chat-quick pc-chat-interactive" id="pc-chat-quick"></div>
        <div class="pc-chat-input-wrap pc-chat-interactive">
            <textarea
                class="pc-chat-input"
                id="pc-chat-input"
                rows="1"
                placeholder="Nhập câu hỏi của bạn..."
                aria-label="Tin nhắn"
                maxlength="2000"
            ></textarea>
            <button type="button" class="pc-chat-send" id="pc-chat-send" aria-label="Gửi tin nhắn">Gửi</button>
        </div>
        <p class="pc-chat-footer-note pc-chat-interactive">
            Hỗ trợ <?= htmlspecialchars((string) ($chatShop['hotline'] ?? ''), ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars((string) ($chatShop['hours'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
        </p>
    </div>
    <button type="button" class="pc-chat-launcher pc-chat-interactive" aria-expanded="false" aria-controls="pc-chat-panel" aria-label="Mở Tư vấn AI">
        <span class="pc-chat-launcher-badge" aria-hidden="true"></span>
        <span class="pc-chat-launcher-icon" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                <path d="M12 3a2 2 0 0 1 2 2v1a2 2 0 0 1-2 2 2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/>
                <path d="M19 10v1a7 7 0 0 1-14 0v-1"/>
                <path d="M12 19v3M8 22h8"/>
            </svg>
        </span>
        <span class="pc-chat-launcher-label">Tư vấn AI</span>
    </button>
</div>
<script>
window.PC_SHOP_CHAT = {
    apiUrl: <?= json_encode(app_url('/api/chat'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>,
    shopName: <?= json_encode((string) ($chatShop['name'] ?? 'PC Parts Shop'), JSON_UNESCAPED_UNICODE) ?>,
    quickActions: <?= json_encode($chatQuickActions, JSON_UNESCAPED_UNICODE) ?>,
    typewriterMaxChars: 900,
    typewriterMsPerChar: 10
};
</script>
<script id="pc-chat-widget-js"><?= $chatJs ?></script>
