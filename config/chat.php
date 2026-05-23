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
    'enabled' => true,

    /** URL Webhook N8N (de trong = che do tra loi mau tren website, khong goi AI) */
    'n8n_webhook_url' => 'https://9jqdl060.rpcld.net/webhook-test/113f864d-6d19-480f-af9b-7ea1fc79f0c7',

    /** Timeout goi N8N (giay). InfinityFree thuong gioi ~30s */
    'timeout_seconds' => 25,

    'shop' => [
        'name' => 'PC Parts Shop',
        'hotline' => '034 969 4556',
        'hours' => '8:00 — 21:00',
        'email' => 'quangminhngo41@gmail.com',
    ],

    /** Gợi ý nhanh hiển thị trong cửa sổ chat */
    'quick_actions' => [
        ['id' => 'promo', 'label' => 'Khuyến mãi', 'message' => 'Cho tôi biết các chương trình khuyến mãi hiện có.'],
        ['id' => 'buy', 'label' => 'Mua hàng online', 'message' => 'Hướng dẫn tôi cách mua hàng và thanh toán trên website.'],
        ['id' => 'advice', 'label' => 'Tư vấn linh kiện', 'message' => 'Tôi cần tư vấn chọn linh kiện phù hợp nhu cầu.'],
        ['id' => 'build', 'label' => 'Build PC', 'message' => 'Giới thiệu tính năng Build PC và cách sử dụng.'],
    ],
];
