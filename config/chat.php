<?php

return [
    'enabled' => filter_var(getenv('CHAT_ENABLED') ?: 'true', FILTER_VALIDATE_BOOLEAN),

    // Production webhook of the n8n chat workflow.
    'n8n_webhook_url' => getenv('N8N_WEBHOOK_URL') ?: '',

    // Optional legacy sync webhook. The recommended RAG flow now reads Aiven MySQL directly from n8n.
    'rag_sync_webhook_url' => getenv('RAG_SYNC_WEBHOOK_URL') ?: '',

    // Optional token for the old /api/ai-context and /api/ai-documents endpoints.
    'ai_context_token' => getenv('AI_CONTEXT_TOKEN') ?: '',

    'timeout_seconds' => (int) (getenv('CHAT_TIMEOUT_SECONDS') ?: 35),

    'shop' => [
        'name' => getenv('SHOP_NAME') ?: 'PC Parts Shop',
        'hotline' => getenv('SHOP_HOTLINE') ?: '034 969 4556',
        'hours' => getenv('SHOP_HOURS') ?: '8:00 - 21:00',
        'email' => getenv('SHOP_EMAIL') ?: 'quangminhngo41@gmail.com',
    ],

    'quick_actions' => [
        ['id' => 'promo', 'label' => 'Khuyen mai', 'message' => 'Cho toi biet cac chuong trinh khuyen mai hien co.'],
        ['id' => 'buy', 'label' => 'Mua hang online', 'message' => 'Huong dan toi cach mua hang va thanh toan tren website.'],
        ['id' => 'advice', 'label' => 'Tu van linh kien', 'message' => 'Toi can tu van chon linh kien phu hop nhu cau.'],
        ['id' => 'build', 'label' => 'Build PC', 'message' => 'Toi muon build mot bo PC, hay goi y cau hinh phu hop tu san pham con hang trong shop.'],
    ],
];
