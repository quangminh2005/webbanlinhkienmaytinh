<?php

declare(strict_types=1);

namespace App\Controllers;

class ChatController
{
    public function send(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $config = $this->loadConfig();
        if (empty($config['enabled'])) {
            http_response_code(503);
            echo json_encode(['ok' => false, 'error' => 'Chat tạm thời tắt'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $raw = file_get_contents('php://input') ?: '';
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Dữ liệu không hợp lệ'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $message = trim((string) ($payload['message'] ?? ''));
        if ($message === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Tin nhắn trống'], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (mb_strlen($message) > 2000) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Tin nhắn quá dài'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $sessionId = trim((string) ($payload['sessionId'] ?? ''));
        if ($sessionId === '') {
            $sessionId = bin2hex(random_bytes(16));
        }

        $context = [
            'sessionId' => $sessionId,
            'pageUrl' => (string) ($payload['pageUrl'] ?? ''),
            'pagePath' => (string) ($payload['pagePath'] ?? ''),
            'quickActionId' => (string) ($payload['quickActionId'] ?? ''),
            'user' => $this->userContext(),
            'shop' => $config['shop'] ?? [],
            'siteBase' => defined('BASE_PATH') ? BASE_PATH : '',
        ];

        $webhookUrl = trim((string) ($config['n8n_webhook_url'] ?? ''));
        if ($webhookUrl !== '') {
            $reply = $this->callN8n($webhookUrl, $message, $context, (int) ($config['timeout_seconds'] ?? 25));
            if ($reply !== null) {
                echo json_encode([
                    'ok' => true,
                    'reply' => $reply,
                    'sessionId' => $sessionId,
                    'source' => 'n8n',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
        }

        $reply = $this->fallbackReply($message, $context, $config);
        echo json_encode([
            'ok' => true,
            'reply' => $reply,
            'sessionId' => $sessionId,
            'source' => 'fallback',
        ], JSON_UNESCAPED_UNICODE);
    }

    /** @return array<string, mixed> */
    private function loadConfig(): array
    {
        $path = dirname(__DIR__, 2) . '/config/chat.php';
        if (!is_file($path)) {
            return ['enabled' => true, 'n8n_webhook_url' => '', 'shop' => []];
        }

        $config = require $path;
        return is_array($config) ? $config : [];
    }

    /** @return array<string, mixed> */
    private function userContext(): array
    {
        $user = $_SESSION['user'] ?? null;
        if (!is_array($user)) {
            return ['loggedIn' => false];
        }

        return [
            'loggedIn' => true,
            'name' => (string) ($user['name'] ?? ''),
            'role' => (string) ($user['role'] ?? 'customer'),
        ];
    }

    /**
     * @param array<string, mixed> $context
     */
    private function callN8n(string $url, string $message, array $context, int $timeout): ?string
    {
        $body = [
            'message' => $message,
            'sessionId' => $context['sessionId'] ?? '',
            'pageUrl' => $context['pageUrl'] ?? '',
            'pagePath' => $context['pagePath'] ?? '',
            'quickActionId' => $context['quickActionId'] ?? '',
            'user' => $context['user'] ?? [],
            'shop' => $context['shop'] ?? [],
            'siteBase' => $context['siteBase'] ?? '',
            'timestamp' => gmdate('c'),
        ];

        $json = json_encode($body, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return null;
        }

        $response = $this->httpPostJson($url, $json, $timeout);
        if ($response === null) {
            return null;
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            $text = trim($response);
            return $text !== '' ? $text : null;
        }

        return $this->extractReply($decoded);
    }

    private function httpPostJson(string $url, string $json, int $timeout): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch === false) {
                return null;
            }

            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $json,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_CONNECTTIMEOUT => min(10, $timeout),
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
            ]);

            $body = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($body === false || $httpCode < 200 || $httpCode >= 300) {
                return null;
            }

            return (string) $body;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
                'content' => $json,
                'timeout' => $timeout,
                'ignore_errors' => true,
            ],
        ]);

        $body = @file_get_contents($url, false, $context);
        if ($body === false) {
            return null;
        }

        return (string) $body;
    }

    /**
     * @param array<string, mixed>|array<int, mixed> $data
     */
    private function extractReply(array $data): ?string
    {
        $keys = ['reply', 'text', 'output', 'message', 'answer', 'response'];
        foreach ($keys as $key) {
            if (!empty($data[$key]) && is_string($data[$key])) {
                return trim($data[$key]);
            }
        }

        if (isset($data[0]) && is_array($data[0])) {
            return $this->extractReply($data[0]);
        }

        if (isset($data['json']) && is_array($data['json'])) {
            return $this->extractReply($data['json']);
        }

        if (isset($data['data']) && is_array($data['data'])) {
            return $this->extractReply($data['data']);
        }

        return null;
    }

    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed> $config
     */
    private function fallbackReply(string $message, array $context, array $config): string
    {
        $folded = $this->foldVietnamese($message);
        $shop = $config['shop'] ?? [];
        $name = (string) ($shop['name'] ?? 'PC Parts Shop');
        $hotline = (string) ($shop['hotline'] ?? '034 969 4556');
        $base = (string) ($context['siteBase'] ?? '');

        if ($this->matchesAny($folded, ['khuyen mai', 'giam gia', 'uu dai', 'promo'])) {
            return "Chào bạn! {$name} thường có ưu đãi theo đợt trên các dòng CPU, VGA, RAM và ổ cứng.\n\n"
                . "Để xem giá mới nhất: vào trang Sản phẩm, lọc theo danh mục hoặc tìm tên linh kiện.\n"
                . "Mua nhiều sản phẩm: thêm vào Giỏ hàng — hệ thống tính tổng trước khi thanh toán.\n\n"
                . "Cần tư vấn gợi ý theo ngân sách? Mô tả nhu cầu (chơi game / văn phòng / render) và ngân sách, trợ lý sẽ gợi ý cấu hình phù hợp.";
        }

        if ($this->matchesAny($folded, ['mua hang', 'thanh toan', 'dat hang', 'checkout', 'giao hang'])) {
            return "Hướng dẫn mua hàng online trên {$name}:\n\n"
                . "1. Chọn sản phẩm → Thêm vào giỏ hàng\n"
                . "2. Vào Giỏ hàng → kiểm tra số lượng\n"
                . "3. Bấm Thanh toán → điền địa chỉ giao hàng\n"
                . "4. Chọn phương thức thanh toán → đặt hàng\n"
                . "5. Đăng nhập để xem đơn tại mục Đơn hàng\n\n"
                . "Đường dẫn: {$base}/cart → {$base}/checkout\n"
                . "Hỗ trợ nhanh: {$hotline}";
        }

        if ($this->matchesAny($folded, ['build pc', 'build-pc', 'tuong thich', 'cau hinh'])) {
            return "Tính năng Build PC giúp chọn CPU, mainboard, RAM, VGA... và cảnh báo tương thích cơ bản.\n\n"
                . "Truy cập: {$base}/build-pc\n"
                . "Chọn từng linh kiện theo thứ tự gợi ý, hệ thống lọc sản phẩm phù hợp socket / loại RAM.\n\n"
                . "Sau khi chọn xong, thêm vào giỏ hàng và thanh toán như mua lẻ.";
        }

        if ($this->matchesAny($folded, ['tu van', 'chon', 'cpu', 'vga', 'ram', 'mainboard', 'ssd', 'linh kien'])) {
            return "Để tư vấn chính xác, bạn cho biết:\n"
                . "• Mục đích: game / học tập / văn phòng / render video\n"
                . "• Ngân sách dự kiến\n"
                . "• Đã có linh kiện nào chưa (nếu nâng cấp)\n\n"
                . "Ví dụ: \"8–12 triệu chơi LOL + học online\" hoặc \"20 triệu render Premiere\".\n"
                . "Hotline: {$hotline}";
        }

        $loggedIn = !empty($context['user']['loggedIn']);
        $userName = trim((string) ($context['user']['name'] ?? ''));
        $greet = $loggedIn && $userName !== ''
            ? 'Xin chào ' . $userName . '! '
            : 'Xin chào! ';

        return $greet . "Tôi là trợ lý {$name}. Tôi có thể:\n"
            . "• Tư vấn linh kiện & Build PC\n"
            . "• Thông tin khuyến mãi\n"
            . "• Hướng dẫn mua hàng & thanh toán online\n\n"
            . "Bạn có thể bấm các gợi ý nhanh bên dưới hoặc đặt câu hỏi cụ thể.\n"
            . "Hotline: {$hotline}";
    }

    /** Bỏ dấu tiếng Việt để nhận diện từ khóa (có dấu hoặc không dấu đều được). */
    private function foldVietnamese(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $map = [
            'à' => 'a', 'á' => 'a', 'ả' => 'a', 'ã' => 'a', 'ạ' => 'a',
            'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a', 'ặ' => 'a',
            'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a', 'ậ' => 'a',
            'è' => 'e', 'é' => 'e', 'ẻ' => 'e', 'ẽ' => 'e', 'ẹ' => 'e',
            'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ể' => 'e', 'ễ' => 'e', 'ệ' => 'e',
            'ì' => 'i', 'í' => 'i', 'ỉ' => 'i', 'ĩ' => 'i', 'ị' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ỏ' => 'o', 'õ' => 'o', 'ọ' => 'o',
            'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ổ' => 'o', 'ỗ' => 'o', 'ộ' => 'o',
            'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ở' => 'o', 'ỡ' => 'o', 'ợ' => 'o',
            'ù' => 'u', 'ú' => 'u', 'ủ' => 'u', 'ũ' => 'u', 'ụ' => 'u',
            'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ử' => 'u', 'ữ' => 'u', 'ự' => 'u',
            'ỳ' => 'y', 'ý' => 'y', 'ỷ' => 'y', 'ỹ' => 'y', 'ỵ' => 'y',
            'đ' => 'd',
        ];

        return strtr($text, $map);
    }

    /** @param string[] $needles */
    private function matchesAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}
