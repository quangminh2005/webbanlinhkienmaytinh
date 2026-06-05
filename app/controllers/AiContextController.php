<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use Throwable;

class AiContextController
{
    public function show(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $config = $this->loadChatConfig();
            if (!$this->isAuthorized((string) ($config['ai_context_token'] ?? ''))) {
                http_response_code(401);
                echo json_encode(['ok' => false, 'error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
                return;
            }

            $query = trim((string) ($_GET['q'] ?? ''));
            $category = trim((string) ($_GET['category'] ?? ''));
            $limit = (int) ($_GET['limit'] ?? 12);
            $limit = max(1, min(30, $limit));

            echo json_encode([
                'ok' => true,
                'shop' => $config['shop'] ?? [],
                'guide' => $this->guide(),
                'categories' => $this->categories(),
                'products' => $this->products($query, $category, $limit),
                'links' => [
                    'home' => $this->absoluteUrl('/'),
                    'cart' => $this->absoluteUrl('/cart'),
                    'checkout' => $this->absoluteUrl('/checkout'),
                    'build_pc' => $this->absoluteUrl('/build-pc'),
                    'orders' => $this->absoluteUrl('/orders'),
                ],
                'filters' => [
                    'q' => $query,
                    'category' => $category,
                    'limit' => $limit,
                ],
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'ok' => false,
                'error' => 'Cannot load AI context',
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    private function loadChatConfig(): array
    {
        $path = dirname(__DIR__, 2) . '/config/chat.php';
        if (!is_file($path)) {
            return [];
        }

        $config = require $path;
        return is_array($config) ? $config : [];
    }

    private function isAuthorized(string $expectedToken): bool
    {
        $expectedToken = trim($expectedToken);
        if ($expectedToken === '') {
            return true;
        }

        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $headerToken = '';
        foreach ($headers as $key => $value) {
            if (strtolower((string) $key) === 'x-ai-context-token') {
                $headerToken = trim((string) $value);
                break;
            }
        }

        $queryToken = trim((string) ($_GET['token'] ?? ''));
        return hash_equals($expectedToken, $headerToken) || hash_equals($expectedToken, $queryToken);
    }

    private function categories(): array
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

    private function products(string $query, string $category, int $limit): array
    {
        $sql = '
            SELECT p.id, p.name, p.price, p.stock_quantity, p.description, p.image_url,
                   p.socket, p.ram_type, p.vram_gb, p.wattage,
                   c.name AS category_name, c.slug AS category_slug
            FROM products p
            JOIN categories c ON c.id = p.category_id
            WHERE 1=1
        ';
        $params = [];

        if ($query !== '') {
            $sql .= ' AND (p.name LIKE :query OR p.description LIKE :query OR c.name LIKE :query)';
            $params['query'] = '%' . $query . '%';
        }

        if ($category !== '') {
            $sql .= ' AND c.slug = :category';
            $params['category'] = $category;
        }

        $sql .= ' ORDER BY p.stock_quantity > 0 DESC, p.id DESC LIMIT ' . $limit;

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        $products = [];
        foreach ($stmt->fetchAll() as $row) {
            $imageUrl = trim((string) ($row['image_url'] ?? ''));
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
                'image_url' => $imageUrl !== '' ? $this->absoluteAssetUrl($imageUrl) : '',
            ];
        }

        return $products;
    }

    private function guide(): array
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

    private function absoluteUrl(string $path): string
    {
        $scheme = 'http';
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $scheme = (string) $_SERVER['HTTP_X_FORWARDED_PROTO'];
        } elseif (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            $scheme = 'https';
        }

        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        return $scheme . '://' . $host . app_url($path);
    }
}
