<?php

/**
 * Cau hinh tro ly AI + N8N.
 *
 * 1. Tao workflow N8N: Webhook (POST) -> AI Agent / OpenAI -> Respond to Webhook
 * 2. Copy URL webhook vao n8n_webhook_url ben duoi
 * 3. Workflow nen tra JSON: { "reply": "noi dung tra loi" }
 *
 * InfinityFree: giu webhook tren may chu N8N/cloud (khong dat URL trong JS).
 */
return [
    'enabled' => filter_var(getenv('CHAT_ENABLED') ?: 'true', FILTER_VALIDATE_BOOLEAN),

    /** URL Webhook N8N (de trong = che do tra loi mau tren website, khong goi AI) */
    'n8n_webhook_url' => getenv('N8N_WEBHOOK_URL') ?: 'https://9jqdl060.rpcld.net/webhook/113f864d-6d19-480f-af9b-7ea1fc79f0c7',

    /** URL Webhook N8N nhan du lieu sync RAG tu website. Dung Production URL khi workflow da publish. */
    'rag_sync_webhook_url' => getenv('RAG_SYNC_WEBHOOK_URL') ?: 'https://9jqdl060.rpcld.net/webhook/pc-shop-rag-sync',

    /** Token rieng de N8N goi /api/ai-context va /api/ai-documents. Nen doi thanh chuoi dai, kho doan khi upload hosting. */
    'ai_context_token' => getenv('AI_CONTEXT_TOKEN') ?: 'pcshop_ai_2026_9XkLm72Q_private',

    /** Timeout goi N8N (giay). InfinityFree thuong gioi ~30s */
    'timeout_seconds' => (int) (getenv('CHAT_TIMEOUT_SECONDS') ?: 25),

    'shop' => [
        'name' => getenv('SHOP_NAME') ?: 'PC Parts Shop',
        'hotline' => getenv('SHOP_HOTLINE') ?: '034 969 4556',
        'hours' => getenv('SHOP_HOURS') ?: '8:00 — 21:00',
        'email' => getenv('SHOP_EMAIL') ?: 'quangminhngo41@gmail.com',
    ],

    /** Gợi ý nhanh hiển thị trong cửa sổ chat */
    'quick_actions' => [
        ['id' => 'promo', 'label' => 'Khuyến mãi', 'message' => 'Cho tôi biết các chương trình khuyến mãi hiện có.'],
        ['id' => 'buy', 'label' => 'Mua hàng online', 'message' => 'Hướng dẫn tôi cách mua hàng và thanh toán trên website.'],
        ['id' => 'advice', 'label' => 'Tư vấn linh kiện', 'message' => 'Tôi cần tư vấn chọn linh kiện phù hợp nhu cầu.'],
        ['id' => 'build', 'label' => 'Build PC', 'message' => 'Giới thiệu tính năng Build PC và cách sử dụng.'],
    ],
];
