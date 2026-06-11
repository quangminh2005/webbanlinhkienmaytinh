<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use Throwable;

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

        $quickActionId = (string) ($payload['quickActionId'] ?? '');
        $pagePath = (string) ($payload['pagePath'] ?? '');
        $currentProductId = $this->productIdFromPagePath($pagePath);
        $currentProduct = $this->currentProductForAi($pagePath);

        $context = [
            'sessionId' => $sessionId,
            'pageUrl' => (string) ($payload['pageUrl'] ?? ''),
            'pagePath' => $pagePath,
            'currentProductId' => $currentProductId,
            'currentProduct' => $currentProduct,
            'quickActionId' => $quickActionId,
            'user' => $this->userContext(),
            'shop' => $config['shop'] ?? [],
            'siteBase' => defined('BASE_PATH') ? BASE_PATH : '',
            'siteUrl' => $this->siteUrl(),
            'contextApiUrl' => $this->absoluteUrl('/api/ai-context'),
            'documentsApiUrl' => $this->absoluteUrl('/api/ai-documents'),
            'contextApiToken' => (string) ($config['ai_context_token'] ?? ''),
            'rag' => [
                'enabled' => true,
                'mode' => 'direct_mysql',
                'mysqlView' => 'ai_rag_documents',
                'documentsApiUrl' => $this->absoluteUrl('/api/ai-documents'),
                'documentsApiToken' => (string) ($config['ai_context_token'] ?? ''),
                'notes' => 'Use n8n RAG/vector search from Aiven MySQL view ai_rag_documents. Do not expect full websiteData in chat payload.',
            ],
        ];

        // Build recommendations require deterministic compatibility checks. Do not let
        // the language model assemble CPU, mainboard and RAM independently from RAG.
        if ($this->shouldUseValidatedBuildReply($message)) {
            echo json_encode([
                'ok' => true,
                'reply' => $this->fallbackBuildReply($message),
                'sessionId' => $sessionId,
                'source' => 'validated_build',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $webhookUrl = trim((string) ($config['n8n_webhook_url'] ?? ''));
        if ($webhookUrl !== '') {
            $n8nMessage = $message;
            if ($quickActionId === 'product_detail' && is_array($currentProduct)) {
                $n8nMessage = $this->messageWithCurrentProduct($message, $currentProduct);
            }

            $reply = $this->callN8n($webhookUrl, $n8nMessage, $context, (int) ($config['timeout_seconds'] ?? 25));
            if ($reply !== null) {
                if ($this->isMainboardCompatibilityQuery($message)) {
                    $validatedReply = $this->validatedMainboardsForCpuReply($message . "\n" . $reply);
                    if ($validatedReply !== null) {
                        $reply = $validatedReply;
                    }
                }
                if ($quickActionId === 'product_detail' && is_array($currentProduct) && $this->isProductNotFoundReply($reply)) {
                    $reply = $this->fallbackProductReply($currentProduct);
                }
                if ($this->isBuildQuery($message) && $this->isGenericBuildGuideReply($reply)) {
                    $reply = $this->fallbackBuildReply($message);
                }
                if (
                    $this->categorySlugsFromQuery($message) !== []
                    && ($this->isGenericIntroReply($reply) || $this->isMissingCategoryDataReply($reply))
                ) {
                    $reply = $this->fallbackCategoryReply($message);
                }

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
            'siteUrl' => $context['siteUrl'] ?? '',
            'contextApiUrl' => $context['contextApiUrl'] ?? '',
            'documentsApiUrl' => $context['documentsApiUrl'] ?? '',
            'contextApiToken' => $context['contextApiToken'] ?? '',
            'currentProductId' => $context['currentProductId'] ?? null,
            'currentProduct' => $context['currentProduct'] ?? null,
            'rag' => $context['rag'] ?? [],
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

    private function absoluteUrl(string $path): string
    {
        return $this->siteUrl() . app_url($path);
    }

    private function siteUrl(): string
    {
        $scheme = 'http';
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $scheme = (string) $_SERVER['HTTP_X_FORWARDED_PROTO'];
        } elseif (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            $scheme = 'https';
        }

        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        return $scheme . '://' . $host;
    }

    private function productIdFromPagePath(string $pagePath): ?int
    {
        $path = parse_url($pagePath, PHP_URL_PATH) ?: '';
        if (!str_ends_with($path, '/product') && $path !== '/product') {
            return null;
        }

        $query = [];
        parse_str((string) (parse_url($pagePath, PHP_URL_QUERY) ?: ''), $query);
        $productId = (int) ($query['id'] ?? 0);

        return $productId > 0 ? $productId : null;
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function websiteDataForAi(string $query, array $config, string $sessionId, string $quickActionId, string $pagePath): array
    {
        try {
            $mode = $this->websiteDataMode($query, $quickActionId);
            $slugs = $this->categorySlugsFromQuery($query);

            return [
                'ok' => true,
                'mode' => $mode,
                'data_status' => 'full',
                'shop' => $config['shop'] ?? [],
                'guide' => $this->guideForAi(),
                'categories' => $this->categoriesForAi(),
                'products' => $this->allProductsForAi(),
                'products_by_category' => $this->allProductsByCategoryForAi(),
                'current_product' => $this->currentProductForAi($pagePath),
                'build_requirements' => $this->buildRequirementsForAi(),
                'links' => [
                    'home' => $this->absoluteUrl('/'),
                    'cart' => $this->absoluteUrl('/cart'),
                    'checkout' => $this->absoluteUrl('/checkout'),
                    'build_pc' => $this->absoluteUrl('/build-pc'),
                    'orders' => $this->absoluteUrl('/orders'),
                ],
                'filters' => [
                    'q' => $query,
                    'category_slugs' => $slugs,
                ],
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'error' => 'Khong lay duoc du lieu website.',
            ];
        }
    }

    private function websiteDataMode(string $query, string $quickActionId): string
    {
        if ($quickActionId === 'build' || $this->isBuildQuery($query)) {
            return 'build';
        }

        if ($this->categorySlugsFromQuery($query) !== []) {
            return 'category';
        }

        $folded = $this->foldVietnamese($query);
        if ($quickActionId === 'buy' || $this->matchesAny($folded, ['mua hang', 'dat hang', 'thanh toan', 'checkout', 'hoan tra', 'doi tra'])) {
            return 'guide';
        }

        if ($quickActionId === 'promo' || $this->matchesAny($folded, ['shop ban gi', 'co gi', 'danh muc', 'san pham gi', 'khuyen mai', 'uu dai'])) {
            return 'summary';
        }

        return 'first';
    }

    /** @return array<int, array<string, mixed>> */
    private function categoriesForAi(): array
    {
        $stmt = Database::connection()->query('
            SELECT c.id, c.name, c.slug, COUNT(p.id) AS product_count
            FROM categories c
            LEFT JOIN products p ON p.category_id = c.id
            GROUP BY c.id, c.name, c.slug
            ORDER BY c.name ASC
        ');

        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    private function allProductsForAi(): array
    {
        $stmt = Database::connection()->query('
            SELECT p.id, p.name, p.price, p.stock_quantity, p.description, p.image_url,
                   p.socket, p.ram_type, p.vram_gb, p.wattage,
                   c.name AS category_name, c.slug AS category_slug
            FROM products p
            JOIN categories c ON c.id = p.category_id
            ORDER BY c.name ASC, p.stock_quantity > 0 DESC, p.id DESC
        ');

        return $this->formatProductRowsForAi($stmt->fetchAll());
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    private function allProductsByCategoryForAi(): array
    {
        $groups = [];
        foreach ($this->categoriesForAi() as $category) {
            $slug = (string) ($category['slug'] ?? '');
            if ($slug === '') {
                continue;
            }

            $groups[$slug] = $this->productsByCategoryForAi($slug, 0);
        }

        return $groups;
    }

    /** @return array<int, array<string, mixed>> */
    private function productsForAi(string $query, int $limit): array
    {
        $limit = max(1, min(20, $limit));
        $sql = '
            SELECT p.id, p.name, p.price, p.stock_quantity, p.description, p.image_url,
                   p.socket, p.ram_type, p.vram_gb, p.wattage,
                   c.name AS category_name, c.slug AS category_slug
            FROM products p
            JOIN categories c ON c.id = p.category_id
            WHERE 1=1
        ';
        $params = [];

        $query = trim($query);
        $categorySlug = $this->categorySlugFromQuery($query);

        if ($categorySlug !== '') {
            $sql .= ' AND c.slug = :category_slug';
            $params['category_slug'] = $categorySlug;
        } elseif ($query !== '') {
            $sql .= ' AND (p.name LIKE :query OR p.description LIKE :query OR c.name LIKE :query)';
            $params['query'] = '%' . $query . '%';
        }

        $sql .= ' ORDER BY p.stock_quantity > 0 DESC, p.id DESC LIMIT ' . $limit;

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return $this->formatProductRowsForAi($stmt->fetchAll());
    }

    /** @return array<string, mixed>|null */
    private function currentProductForAi(string $pagePath): ?array
    {
        $path = parse_url($pagePath, PHP_URL_PATH) ?: '';
        if (!str_ends_with($path, '/product') && $path !== '/product') {
            return null;
        }

        $query = [];
        parse_str((string) (parse_url($pagePath, PHP_URL_QUERY) ?: ''), $query);
        $productId = (int) ($query['id'] ?? 0);
        if ($productId <= 0) {
            return null;
        }

        $stmt = Database::connection()->prepare('
            SELECT p.id, p.name, p.price, p.stock_quantity, p.description, p.image_url,
                   p.socket, p.ram_type, p.vram_gb, p.wattage,
                   c.name AS category_name, c.slug AS category_slug
            FROM products p
            JOIN categories c ON c.id = p.category_id
            WHERE p.id = :id
            LIMIT 1
        ');
        $stmt->execute(['id' => $productId]);
        $product = $stmt->fetch();
        if (!$product) {
            return null;
        }

        return [
            'id' => (int) $product['id'],
            'name' => (string) $product['name'],
            'category' => (string) $product['category_name'],
            'category_slug' => (string) $product['category_slug'],
            'price' => (float) $product['price'],
            'price_text' => number_format((float) $product['price']) . ' VND',
            'stock_quantity' => (int) $product['stock_quantity'],
            'in_stock' => (int) $product['stock_quantity'] > 0,
            'description' => (string) ($product['description'] ?? ''),
            'specs' => [
                'socket' => (string) ($product['socket'] ?? ''),
                'ram_type' => (string) ($product['ram_type'] ?? ''),
                'vram_gb' => (int) ($product['vram_gb'] ?? 0),
                'wattage' => (int) ($product['wattage'] ?? 0),
            ],
            'url' => $this->absoluteUrl('/product?id=' . (int) $product['id']),
        ];
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    private function buildProductsForAi(string $query): array
    {
        if (!$this->isBuildQuery($query)) {
            return [];
        }

        $groups = [];
        foreach ($this->buildCategorySlugs() as $slug) {
            $groups[$slug] = $this->productsByCategoryForAi($slug, 3);
        }

        return $groups;
    }

    /** @return array<string, mixed> */
    private function buildRequirementsForAi(): array
    {
        return [
            'required' => ['cpu', 'mainboard', 'ram', 'vga', 'psu', 'case', 'ssd', 'cooler'],
            'optional' => ['hdd'],
            'labels' => [
                'cpu' => 'CPU',
                'mainboard' => 'Mainboard',
                'ram' => 'RAM',
                'vga' => 'VGA',
                'psu' => 'Nguon',
                'case' => 'Case',
                'ssd' => 'SSD',
                'cooler' => 'Tan nhiet',
                'hdd' => 'HDD',
            ],
        ];
    }

    /** @return string[] */
    private function buildCategorySlugs(): array
    {
        $available = [];
        foreach ($this->categoriesForAi() as $category) {
            $slug = (string) ($category['slug'] ?? '');
            if ($slug !== '') {
                $available[] = $slug;
            }
        }

        $slugs = [];
        foreach (['cpu', 'mainboard', 'ram', 'vga', 'psu', 'case', 'ssd', 'cooler', 'hdd'] as $slug) {
            if (in_array($slug, $available, true)) {
                $slugs[] = $slug;
            }
        }

        return $slugs;
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    private function requestedCategoryProductsForAi(string $query): array
    {
        $slugs = $this->categorySlugsFromQuery($query);
        if ($slugs === []) {
            return [];
        }

        $groups = [];
        foreach ($slugs as $slug) {
            $groups[$slug] = $this->productsByCategoryForAi($slug, 8);
        }

        return $groups;
    }

    /** @return array<int, array<string, mixed>> */
    private function productsByCategoryForAi(string $categorySlug, int $limit): array
    {
        $sql = '
            SELECT p.id, p.name, p.price, p.stock_quantity, p.description, p.image_url,
                   p.socket, p.ram_type, p.vram_gb, p.wattage,
                   c.name AS category_name, c.slug AS category_slug
            FROM products p
            JOIN categories c ON c.id = p.category_id
            WHERE c.slug = :category_slug
            ORDER BY p.stock_quantity > 0 DESC, p.price DESC, p.id DESC
        ';

        if ($limit > 0) {
            $limit = max(1, min(50, $limit));
            $sql .= ' LIMIT ' . $limit;
        }

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['category_slug' => $categorySlug]);

        return $this->formatProductRowsForAi($stmt->fetchAll());
    }

    /** @param array<int, array<string, mixed>> $rows */
    private function formatProductRowsForAi(array $rows): array
    {
        $products = [];
        foreach ($rows as $row) {
            $products[] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'category' => (string) $row['category_name'],
                'category_slug' => (string) $row['category_slug'],
                'price' => (float) $row['price'],
                'price_text' => number_format((float) $row['price']) . ' VND',
                'stock_quantity' => (int) $row['stock_quantity'],
                'in_stock' => (int) $row['stock_quantity'] > 0,
                'description' => (string) ($row['description'] ?? ''),
                'specs' => [
                    'socket' => (string) ($row['socket'] ?? ''),
                    'ram_type' => (string) ($row['ram_type'] ?? ''),
                    'vram_gb' => (int) ($row['vram_gb'] ?? 0),
                    'wattage' => (int) ($row['wattage'] ?? 0),
                ],
                'url' => $this->absoluteUrl('/product?id=' . (int) $row['id']),
            ];
        }

        return $products;
    }

    private function shortDescription(string $description): string
    {
        $description = trim(preg_replace('/\s+/', ' ', $description) ?? '');
        if (mb_strlen($description, 'UTF-8') <= 350) {
            return $description;
        }

        return mb_substr($description, 0, 350, 'UTF-8') . '...';
    }

    private function isBuildQuery(string $query): bool
    {
        $folded = $this->foldVietnamese($query);
        foreach (['build', 'cau hinh', 'pc', 'may tinh', 'combo', 'tam gia', 'ngan sach', 'trieu'] as $term) {
            if (str_contains($folded, $term)) {
                return true;
            }
        }

        return false;
    }

    private function shouldUseValidatedBuildReply(string $query): bool
    {
        $folded = $this->foldVietnamese($query);

        return str_contains($folded, 'build')
            || str_contains($folded, 'cau hinh pc')
            || str_contains($folded, 'lap pc')
            || str_contains($folded, 'rap pc');
    }

    private function isMainboardCompatibilityQuery(string $query): bool
    {
        $folded = $this->foldVietnamese($query);
        $mentionsMainboard = $this->matchesAny($folded, [
            'main tuong thich',
            'mainboard tuong thich',
            'main phu hop',
            'mainboard phu hop',
            'bo mach chu tuong thich',
            'bo mach chu phu hop',
        ]);

        return $mentionsMainboard
            || ($this->matchesAny($folded, ['main', 'mainboard', 'bo mach chu'])
                && $this->matchesAny($folded, ['cpu nay', 'cpu do', 'cpu tren', 'tuong thich', 'phu hop']));
    }

    private function validatedMainboardsForCpuReply(string $contextText): ?string
    {
        $cpu = $this->productMentionedInText('cpu', $contextText);
        if ($cpu === null) {
            return null;
        }

        $socket = trim((string) ($cpu['specs']['socket'] ?? ''));
        if ($socket === '') {
            return null;
        }

        $stmt = Database::connection()->prepare("
            SELECT p.id, p.name, p.price, p.stock_quantity, p.description, p.image_url,
                   p.socket, p.ram_type, p.vram_gb, p.wattage,
                   c.name AS category_name, c.slug AS category_slug
            FROM products p
            JOIN categories c ON c.id = p.category_id
            WHERE c.slug = :category_slug
              AND p.stock_quantity > 0
              AND UPPER(REPLACE(REPLACE(TRIM(p.socket), ' ', ''), '-', '')) = :socket
            ORDER BY p.price ASC, p.id ASC
        ");
        $stmt->execute([
            'category_slug' => 'mainboard',
            'socket' => $this->normalizeCompatibilityValue($socket),
        ]);
        $mainboards = $this->formatProductRowsForAi($stmt->fetchAll());

        if ($mainboards === []) {
            return 'Hiện shop chưa có mainboard còn hàng dùng socket ' . $socket
                . ' tương thích với CPU ' . (string) $cpu['name'] . '.';
        }

        $lines = [
            'Các mainboard còn hàng tương thích với CPU ' . (string) $cpu['name']
                . ' (socket ' . $socket . ') gồm:',
            '',
        ];

        foreach ($mainboards as $index => $mainboard) {
            $ramType = trim((string) ($mainboard['specs']['ram_type'] ?? ''));
            $mainboardSocket = trim((string) ($mainboard['specs']['socket'] ?? ''));
            $lines[] = ($index + 1) . '. ' . (string) $mainboard['name']
                . ' | Giá: ' . (string) $mainboard['price_text']
                . ' | Socket: ' . $mainboardSocket
                . ($ramType !== '' ? ' | RAM: ' . $ramType : '')
                . ' | Tồn kho: ' . (int) $mainboard['stock_quantity']
                . ' | Link: ' . (string) $mainboard['url'];
        }

        return implode("\n", $lines);
    }

    /** @return array<string, mixed>|null */
    private function productMentionedInText(string $categorySlug, string $text): ?array
    {
        $products = $this->productsByCategoryForAi($categorySlug, 0);
        if ($products === []) {
            return null;
        }

        $foldedText = $this->foldVietnamese($text);
        $bestProduct = null;
        $bestLength = 0;

        foreach ($products as $product) {
            $name = trim((string) ($product['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $foldedName = $this->foldVietnamese($name);
            if ($foldedName !== '' && str_contains($foldedText, $foldedName) && mb_strlen($foldedName) > $bestLength) {
                $bestProduct = $product;
                $bestLength = mb_strlen($foldedName);
                continue;
            }

            foreach (preg_split('/[^a-z0-9]+/', $foldedName) ?: [] as $token) {
                if (strlen($token) < 5 || !preg_match('/\d/', $token)) {
                    continue;
                }

                if (str_contains($foldedText, $token) && strlen($token) > $bestLength) {
                    $bestProduct = $product;
                    $bestLength = strlen($token);
                }
            }
        }

        return $bestProduct;
    }

    private function categorySlugFromQuery(string $query): string
    {
        $slugs = $this->categorySlugsFromQuery($query);
        return $slugs[0] ?? '';
    }

    /** @return string[] */
    private function categorySlugsFromQuery(string $query): array
    {
        $folded = $this->foldVietnamese($query);
        $lower = mb_strtolower($query, 'UTF-8');

        $keywords = [
            'cpu' => ['cpu', 'processor', 'vi xu ly', 'vi xử lý', 'bo xu ly', 'bộ xử lý', 'chip'],
            'vga' => ['vga', 'gpu', 'card man hinh', 'card màn hình', 'card do hoa', 'card đồ họa', 'card roi', 'card rời'],
            'ram' => ['ram', 'bo nho', 'bộ nhớ', 'memory'],
            'mainboard' => ['mainboard', 'main board', 'main', 'bo mach chu', 'bo mạch chủ', 'motherboard'],
            'psu' => ['psu', 'nguon', 'nguồn', 'nguon may tinh', 'nguồn máy tính', 'power supply'],
            'ssd' => ['ssd', 'o cung ssd', 'ổ cứng ssd', 'o ssd', 'ổ ssd'],
            'hdd' => ['hdd', 'o cung hdd', 'ổ cứng hdd', 'o hdd', 'ổ hdd'],
            'case' => ['case', 'vo case', 'vỏ case', 'vo may', 'vỏ máy', 'thung may', 'thùng máy'],
            'cooler' => ['cooler', 'tan nhiet', 'tản nhiệt', 'tản nhiêt', 'aio'],
            'monitor' => ['monitor', 'man hinh', 'màn hình'],
            'mouse' => ['mouse', 'chuot', 'chuột'],
            'keyboard' => ['keyboard', 'ban phim', 'bàn phím', 'phim'],
        ];

        $extraKeywords = [
            'cooler' => ['fan cpu', 'quat cpu', 'radiator', 'heatsink'],
            'monitor' => ['screen', 'display'],
            'keyboard' => ['phim co', 'keycap'],
            'mouse' => ['gaming mouse'],
        ];
        foreach ($extraKeywords as $slug => $terms) {
            $keywords[$slug] = array_merge($keywords[$slug] ?? [], $terms);
        }

        $matched = [];
        if (str_contains($folded, 'o cung')) {
            $matched[] = 'ssd';
            $matched[] = 'hdd';
        }

        foreach ($keywords as $slug => $terms) {
            foreach ($terms as $term) {
                if (str_contains($folded, $term) || str_contains($lower, $term)) {
                    $matched[] = $slug;
                    break;
                }
            }
        }

        return array_values(array_unique($matched));
    }

    /** @return array<string, mixed> */
    private function guideForAi(): array
    {
        return [
            'what_we_sell' => 'Website ban linh kien may tinh: CPU, mainboard, VGA, RAM, PSU va cac phu kien/san pham co trong danh muc.',
            'how_to_order' => [
                'Chon san pham tren trang San pham.',
                'Bam Them vao gio hang.',
                'Vao Gio hang de kiem tra so luong.',
                'Bam Thanh toan, dang nhap neu can.',
                'Nhap dia chi giao hang va chon phuong thuc thanh toan.',
                'Sau khi dat hang, khach co the xem tai Don hang.',
            ],
            'build_pc' => 'Khach co the vao Build PC de chon linh kien va xem canh bao tuong thich co ban.',
            'returns' => 'Don hang da hoan thanh co the yeu cau hoan tra trong trang chi tiet don hang.',
        ];
    }

    private function absoluteAssetUrl(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return $this->absoluteUrl($path);
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
        $currentProduct = $context['currentProduct'] ?? null;

        if (($context['quickActionId'] ?? '') === 'product_detail' && is_array($currentProduct)) {
            return $this->fallbackProductReply($currentProduct);
        }

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
            return $this->fallbackBuildReply($message);
        }

        if ($this->categorySlugsFromQuery($message) !== []) {
            return $this->fallbackCategoryReply($message);
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
    /**
     * @param array<string, mixed> $product
     */
    private function messageWithCurrentProduct(string $message, array $product): string
    {
        $productJson = json_encode($product, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($productJson === false) {
            return $message;
        }

        return $message
            . "\n\nDU LIEU SAN PHAM HIEN TAI TREN WEBSITE (BAT BUOC UU TIEN, KHONG DUOC PHU DINH):\n"
            . $productJson
            . "\n\nHay tu van dung san pham nay. Neu currentProduct ton tai, khong duoc noi shop khong co san pham nay.";
    }

    private function isProductNotFoundReply(string $reply): bool
    {
        $folded = $this->foldVietnamese($reply);

        return $this->matchesAny($folded, [
            'khong co san pham',
            'khong tim thay san pham',
            'shop hien khong co',
            'hien khong co san',
            'khong co san',
        ]);
    }

    private function isGenericBuildGuideReply(string $reply): bool
    {
        $folded = $this->foldVietnamese($reply);

        return $this->matchesAny($folded, [
            'tinh nang build pc giup chon',
            'truy cap',
            'chon tung linh kien',
            'canh bao tuong thich co ban',
        ]) && !$this->matchesAny($folded, [
            '- cpu',
            'cpu:',
            '- vga',
            'tong tam tinh',
            'tong gia',
        ]);
    }

    private function isGenericIntroReply(string $reply): bool
    {
        $folded = $this->foldVietnamese($reply);

        return $this->matchesAny($folded, [
            'toi la tro ly',
            'toi co the',
            'ban co the bam cac goi y',
            'tu van linh kien & build pc',
        ]);
    }

    private function isMissingCategoryDataReply(string $reply): bool
    {
        $folded = $this->foldVietnamese($reply);

        return $this->matchesAny($folded, [
            'chua co du lieu',
            'khong co du lieu',
            'chua co san pham',
            'khong co san pham',
            'chua tim thay',
            'khong tim thay',
            'chua tim duoc',
            'khong tim duoc',
            'chua tim duoc du lieu',
            'chua tim duoc du lieu man hinh',
            'chua tim duoc du lieu chuot',
            'chua tim duoc du lieu ban phim',
            'con hang cu the',
            'chua co du lieu chuot',
            'chua co du lieu ban phim',
            'chua co du lieu man hinh',
        ]);
    }

    private function fallbackCategoryReply(string $message): string
    {
        $groups = $this->requestedCategoryProductsForAi($message);
        if ($groups === []) {
            return "Mình chưa tìm thấy danh mục phù hợp trong dữ liệu shop. Anh/chị có thể nói rõ muốn xem CPU, VGA, RAM, màn hình, bàn phím hay chuột nhé.";
        }

        $labels = [
            'cpu' => 'CPU',
            'vga' => 'VGA',
            'ram' => 'RAM',
            'mainboard' => 'Mainboard',
            'psu' => 'Nguồn/PSU',
            'ssd' => 'SSD',
            'hdd' => 'HDD',
            'case' => 'Case',
            'cooler' => 'Tản nhiệt',
            'monitor' => 'Màn hình',
            'mouse' => 'Chuột',
            'keyboard' => 'Bàn phím',
        ];

        $lines = ['Hiện shop có các sản phẩm phù hợp để anh/chị tham khảo:', ''];
        foreach ($groups as $slug => $products) {
            $lines[] = ($labels[$slug] ?? strtoupper((string) $slug)) . ':';
            if ($products === []) {
                $lines[] = '- Chưa có sản phẩm còn dữ liệu trong danh mục này.';
                $lines[] = '';
                continue;
            }

            foreach (array_slice($products, 0, 5) as $product) {
                $stock = (int) ($product['stock_quantity'] ?? 0);
                $lines[] = '- ' . (string) $product['name']
                    . ' | Giá: ' . (string) $product['price_text']
                    . ' | Tồn kho: ' . $stock
                    . ' | ' . ($stock > 0 ? 'Còn hàng' : 'Hết hàng')
                    . ' | Link: ' . (string) $product['url'];
            }
            $lines[] = '';
        }

        $lines[] = 'Anh/chị có thể bấm link chi tiết để xem thông số và thêm vào giỏ hàng.';

        return implode("\n", $lines);
    }

    private function fallbackBuildReply(string $message): string
    {
        $budget = $this->budgetFromMessage($message);
        $items = $this->fallbackBuildItems($budget, $message);
        $compatibilityErrors = $this->validateBuildCompatibility($items, true);
        if ($items === [] || $compatibilityErrors !== []) {
            return "Hiện shop chưa có đủ dữ liệu linh kiện còn hàng để chốt cấu hình PC hoàn chỉnh. Anh/chị có thể vào Build PC để chọn trực tiếp: "
                . $this->absoluteUrl('/build-pc');
        }

        $labels = $this->buildRequirementsForAi()['labels'];
        $required = $this->buildRequirementsForAi()['required'];
        $lines = [];
        $lines[] = $budget > 0
            ? 'Mình gợi ý cấu hình PC theo ngân sách khoảng ' . number_format($budget) . ' VND từ sản phẩm còn hàng trong shop:'
            : 'Mình gợi ý cấu hình PC từ sản phẩm còn hàng trong shop:';
        $lines[] = '';

        $total = 0.0;
        $missing = [];
        foreach ($required as $slug) {
            $product = $items[$slug] ?? null;
            if (!$product) {
                $missing[] = (string) ($labels[$slug] ?? $slug);
                continue;
            }

            $total += (float) $product['price'];
            $lines[] = '- ' . ($labels[$slug] ?? strtoupper($slug)) . ': ' . $product['name']
                . ' | Giá: ' . $product['price_text']
                . ' | Tồn kho: ' . (int) $product['stock_quantity']
                . ' | Link: ' . $product['url'];
        }

        $lines[] = '';
        $lines[] = 'Tổng tạm tính: ' . number_format($total) . ' VND';
        if ($budget > 0) {
            $diff = $total - $budget;
            $lines[] = $diff > 0
                ? 'Cấu hình đang vượt ngân sách khoảng ' . number_format($diff) . ' VND.'
                : 'Cấu hình còn dư ngân sách khoảng ' . number_format(abs($diff)) . ' VND.';
        }

        if ($missing !== []) {
            $lines[] = '';
            $lines[] = 'Chưa đủ dữ liệu còn hàng cho nhóm: ' . implode(', ', $missing) . '.';
        }

        $lines[] = '';
        $lines[] = 'Anh/chị có thể kiểm tra và thêm vào giỏ tại trang Build PC: ' . $this->absoluteUrl('/build-pc');

        return implode("\n", $lines);
    }

    /** @return array<string, array<string, mixed>> */
    private function fallbackBuildItems(float $budget, string $message): array
    {
        $weights = [
            'cpu' => 0.18,
            'mainboard' => 0.12,
            'ram' => 0.08,
            'vga' => 0.38,
            'psu' => 0.07,
            'case' => 0.06,
            'ssd' => 0.07,
            'cooler' => 0.04,
        ];

        $foldedMessage = $this->foldVietnamese($message);
        $preferCheapest = $this->matchesAny($foldedMessage, [
            're nhat', 'gia re', 'thap nhat', 'tiet kiem', 're nhat co the',
        ]);

        $items = [];
        $requestedMainboard = $this->fallbackRequestedBuildProduct('mainboard', $message);
        if ($requestedMainboard !== null) {
            $items['mainboard'] = $requestedMainboard;
        }

        $requestedCase = $this->fallbackRequestedBuildProduct('case', $message);
        if ($requestedCase !== null) {
            $items['case'] = $requestedCase;
        }

        foreach ($this->buildRequirementsForAi()['required'] as $slug) {
            if (isset($items[(string) $slug])) {
                continue;
            }

            $target = $budget > 0 ? $budget * (float) ($weights[$slug] ?? 0.1) : 0;
            $constraints = [];
            if (isset($items['mainboard'])) {
                $mainSpecs = is_array($items['mainboard']['specs'] ?? null) ? $items['mainboard']['specs'] : [];
                if ($slug === 'cpu' && !empty($mainSpecs['socket'])) {
                    $constraints['socket'] = (string) $mainSpecs['socket'];
                }
                if ($slug === 'ram' && !empty($mainSpecs['ram_type'])) {
                    $constraints['ram_type'] = (string) $mainSpecs['ram_type'];
                }
            }
            if ($slug === 'mainboard' && isset($items['cpu'])) {
                $cpuSpecs = is_array($items['cpu']['specs'] ?? null) ? $items['cpu']['specs'] : [];
                if (!empty($cpuSpecs['socket'])) {
                    $constraints['socket'] = (string) $cpuSpecs['socket'];
                }
            }
            if ($slug === 'psu' && isset($items['vga'])) {
                $vgaSpecs = is_array($items['vga']['specs'] ?? null) ? $items['vga']['specs'] : [];
                $vgaWattage = (int) ($vgaSpecs['wattage'] ?? 0);
                if ($vgaWattage > 0) {
                    $constraints['wattage_min'] = $this->minimumPsuWattage($vgaWattage);
                }
            }

            $product = $this->fallbackBuildProductByCategory(
                (string) $slug,
                $target,
                $constraints,
                $preferCheapest
            );
            if ($product !== null) {
                $items[(string) $slug] = $product;
            }
        }

        $cpuSpecs = is_array($items['cpu']['specs'] ?? null) ? $items['cpu']['specs'] : [];
        $mainSpecs = is_array($items['mainboard']['specs'] ?? null) ? $items['mainboard']['specs'] : [];
        $cpuSocket = $this->normalizeCompatibilityValue((string) ($cpuSpecs['socket'] ?? ''));
        $mainSocket = $this->normalizeCompatibilityValue((string) ($mainSpecs['socket'] ?? ''));

        if ($cpuSocket !== '' && $mainSocket !== '' && $cpuSocket !== $mainSocket) {
            $replacementMain = $this->fallbackBuildProductByCategory(
                'mainboard',
                $budget > 0 ? $budget * (float) $weights['mainboard'] : 0,
                ['socket' => (string) ($cpuSpecs['socket'] ?? '')],
                $preferCheapest
            );
            if ($replacementMain !== null) {
                $items['mainboard'] = $replacementMain;
                $mainSpecs = is_array($replacementMain['specs'] ?? null) ? $replacementMain['specs'] : [];
            }
        }

        $mainRamType = $this->normalizeCompatibilityValue((string) ($mainSpecs['ram_type'] ?? ''));
        $ramSpecs = is_array($items['ram']['specs'] ?? null) ? $items['ram']['specs'] : [];
        $ramType = $this->normalizeCompatibilityValue((string) ($ramSpecs['ram_type'] ?? ''));

        if ($mainRamType !== '' && $ramType !== $mainRamType) {
            $replacementRam = $this->fallbackBuildProductByCategory(
                'ram',
                $budget > 0 ? $budget * (float) $weights['ram'] : 0,
                ['ram_type' => (string) ($mainSpecs['ram_type'] ?? '')],
                $preferCheapest
            );
            if ($replacementRam !== null) {
                $items['ram'] = $replacementRam;
            } else {
                unset($items['ram']);
            }
        }

        $vgaSpecs = is_array($items['vga']['specs'] ?? null) ? $items['vga']['specs'] : [];
        $psuSpecs = is_array($items['psu']['specs'] ?? null) ? $items['psu']['specs'] : [];
        $vgaWattage = (int) ($vgaSpecs['wattage'] ?? 0);
        $psuWattage = (int) ($psuSpecs['wattage'] ?? 0);
        if ($vgaWattage > 0 && $psuWattage < $this->minimumPsuWattage($vgaWattage)) {
            $replacementPsu = $this->fallbackBuildProductByCategory(
                'psu',
                $budget > 0 ? $budget * (float) $weights['psu'] : 0,
                ['wattage_min' => $this->minimumPsuWattage($vgaWattage)],
                $preferCheapest
            );
            if ($replacementPsu !== null) {
                $items['psu'] = $replacementPsu;
            } else {
                unset($items['psu']);
            }
        }

        return $items;
    }

    /** @return array<string, mixed>|null */
    private function fallbackBuildProductByCategory(
        string $categorySlug,
        float $targetPrice,
        array $constraints = [],
        bool $preferCheapest = false
    ): ?array
    {
        $sql = '
            SELECT p.id, p.name, p.price, p.stock_quantity, p.description, p.image_url,
                   p.socket, p.ram_type, p.vram_gb, p.wattage,
                   c.name AS category_name, c.slug AS category_slug
            FROM products p
            JOIN categories c ON c.id = p.category_id
            WHERE c.slug = :category_slug
              AND p.stock_quantity > 0
        ';

        $params = ['category_slug' => $categorySlug];
        if (!empty($constraints['socket'])) {
            $sql .= " AND UPPER(REPLACE(REPLACE(TRIM(p.socket), ' ', ''), '-', '')) = :socket";
            $params['socket'] = $this->normalizeCompatibilityValue((string) $constraints['socket']);
        }
        if (!empty($constraints['ram_type'])) {
            $sql .= " AND UPPER(REPLACE(REPLACE(TRIM(p.ram_type), ' ', ''), '-', '')) = :ram_type";
            $params['ram_type'] = $this->normalizeCompatibilityValue((string) $constraints['ram_type']);
        }
        if (!empty($constraints['wattage_min'])) {
            $sql .= ' AND p.wattage >= :wattage_min';
            $params['wattage_min'] = (int) $constraints['wattage_min'];
        }

        $orderBy = $preferCheapest
            ? 'p.price ASC, p.id ASC'
            : (($targetPrice > 0 ? 'ABS(p.price - :target_price) ASC,' : '') . ' p.price DESC, p.id DESC');

        $sql .= '
            ORDER BY ' . $orderBy . '
            LIMIT 1
        ';

        if ($targetPrice > 0) {
            $params['target_price'] = $targetPrice;
        }

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        $rows = $this->formatProductRowsForAi($stmt->fetchAll());

        return $rows[0] ?? null;
    }

    private function normalizeCompatibilityValue(string $value): string
    {
        return strtoupper(preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($value))) ?? '');
    }

    private function minimumPsuWattage(int $vgaWattage): int
    {
        return max(450, $vgaWattage + 250);
    }

    /** @param array<string, array<string, mixed>> $items */
    private function validateBuildCompatibility(array $items, bool $requireComplete = false): array
    {
        $errors = [];

        if ($requireComplete) {
            foreach ($this->buildRequirementsForAi()['required'] as $slug) {
                if (empty($items[(string) $slug])) {
                    $errors[] = 'Thieu linh kien bat buoc: ' . (string) $slug;
                }
            }
        }

        foreach ($items as $product) {
            if ((int) ($product['stock_quantity'] ?? 0) <= 0) {
                $errors[] = 'Linh kien het hang: ' . (string) ($product['name'] ?? 'khong xac dinh');
            }
        }

        if (isset($items['cpu'], $items['mainboard'])) {
            $cpuSocket = $this->buildSpec($items['cpu'], 'socket');
            $mainSocket = $this->buildSpec($items['mainboard'], 'socket');
            if ($cpuSocket === '' || $mainSocket === '') {
                $errors[] = 'Khong du du lieu socket CPU/Mainboard de xac minh.';
            } elseif ($cpuSocket !== $mainSocket) {
                $errors[] = 'CPU va Mainboard khong cung socket.';
            }
        }

        if (isset($items['mainboard'], $items['ram'])) {
            $mainRamType = $this->buildSpec($items['mainboard'], 'ram_type');
            $ramType = $this->buildSpec($items['ram'], 'ram_type');
            if ($mainRamType === '' || $ramType === '') {
                $errors[] = 'Khong du du lieu RAM type de xac minh Mainboard/RAM.';
            } elseif ($mainRamType !== $ramType) {
                $errors[] = 'Mainboard va RAM khong cung chuan RAM.';
            }
        }

        if (isset($items['vga'], $items['psu'])) {
            $vgaWattage = (int) ($items['vga']['specs']['wattage'] ?? 0);
            $psuWattage = (int) ($items['psu']['specs']['wattage'] ?? 0);
            if ($vgaWattage <= 0 || $psuWattage <= 0) {
                $errors[] = 'Khong du du lieu cong suat VGA/PSU de xac minh.';
            } elseif ($psuWattage < $this->minimumPsuWattage($vgaWattage)) {
                $errors[] = 'Nguon khong du cong suat du phong cho VGA.';
            }
        }

        return array_values(array_unique($errors));
    }

    /** @param array<string, mixed> $product */
    private function buildSpec(array $product, string $key): string
    {
        $specs = is_array($product['specs'] ?? null) ? $product['specs'] : [];
        return $this->normalizeCompatibilityValue((string) ($specs[$key] ?? ''));
    }

    /** @return array<string, mixed>|null */
    private function fallbackRequestedBuildProduct(string $categorySlug, string $message): ?array
    {
        $products = $this->productsByCategoryForAi($categorySlug, 0);
        if ($products === []) {
            return null;
        }

        $foldedMessage = $this->foldVietnamese($message);
        $bestProduct = null;
        $bestScore = 0;

        foreach ($products as $product) {
            if (empty($product['in_stock'])) {
                continue;
            }

            $score = $this->productMentionScore((string) $product['name'], $foldedMessage);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestProduct = $product;
            }
        }

        return $bestScore > 0 ? $bestProduct : null;
    }

    private function productMentionScore(string $productName, string $foldedMessage): int
    {
        $foldedName = $this->foldVietnamese($productName);
        preg_match_all('/[a-z0-9]+/i', $foldedName, $matches);

        $ignored = [
            'main', 'mainboard', 'motherboard', 'case', 'cpu', 'ram', 'vga', 'psu', 'ssd', 'hdd',
            'pc', 'build', 'dung', 'hoan', 'thien', 'linh', 'kien', 'con', 'lai', 'them', 'voi',
            'asus', 'gigabyte', 'msi',
        ];

        $score = 0;
        foreach (array_unique($matches[0] ?? []) as $token) {
            $token = strtolower($token);
            if (strlen($token) < 3 || in_array($token, $ignored, true)) {
                continue;
            }
            if (str_contains($foldedMessage, $token)) {
                $score++;
            }
        }

        return $score;
    }

    private function budgetFromMessage(string $message): float
    {
        $folded = $this->foldVietnamese($message);
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*(trieu|tr)/u', $folded, $match)) {
            return (float) str_replace(',', '.', $match[1]) * 1000000;
        }

        if (preg_match('/(\d[\d.,]{5,})/u', $folded, $match)) {
            return (float) preg_replace('/[^\d]/', '', $match[1]);
        }

        return 0.0;
    }

    /**
     * @param array<string, mixed> $product
     */
    private function fallbackProductReply(array $product): string
    {
        $name = (string) ($product['name'] ?? 'san pham nay');
        $category = (string) ($product['category'] ?? '');
        $price = (string) ($product['price_text'] ?? '');
        $stock = (int) ($product['stock_quantity'] ?? 0);
        $description = trim((string) ($product['description'] ?? ''));
        $url = (string) ($product['url'] ?? '');
        $specs = is_array($product['specs'] ?? null) ? $product['specs'] : [];

        $lines = [
            "Mình tư vấn nhanh về {$name}:",
            '',
        ];

        if ($category !== '') {
            $lines[] = "- Danh mục: {$category}";
        }
        if ($price !== '') {
            $lines[] = "- Giá: {$price}";
        }
        $lines[] = "- Tồn kho: {$stock} chiếc (" . ($stock > 0 ? 'còn hàng' : 'hết hàng') . ')';

        $specLines = [];
        foreach ([
            'socket' => 'Socket',
            'ram_type' => 'RAM type',
            'vram_gb' => 'VRAM',
            'wattage' => 'Wattage',
        ] as $key => $label) {
            $value = $specs[$key] ?? null;
            if ($value === null || $value === '' || $value === 0) {
                continue;
            }
            $suffix = $key === 'vram_gb' ? ' GB' : ($key === 'wattage' ? 'W' : '');
            $specLines[] = "- {$label}: {$value}{$suffix}";
        }

        if ($specLines !== []) {
            $lines[] = '';
            $lines[] = 'Thông số chính:';
            array_push($lines, ...$specLines);
        }

        if ($description !== '') {
            $lines[] = '';
            $lines[] = 'Mô tả/thông số từ shop:';
            $lines[] = $description;
        }

        if ($url !== '') {
            $lines[] = '';
            $lines[] = "Link chi tiết: {$url}";
        }

        return implode("\n", $lines);
    }

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
