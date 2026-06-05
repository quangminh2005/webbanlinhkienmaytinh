<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use Throwable;

class AiDocumentsController
{
    public function index(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $config = $this->loadChatConfig();
            if (!$this->isAuthorized((string) ($config['ai_context_token'] ?? ''))) {
                http_response_code(401);
                echo json_encode(['ok' => false, 'error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
                return;
            }

            echo json_encode([
                'ok' => true,
                'documents' => $this->documents(),
                'generated_at' => gmdate('c'),
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'ok' => false,
                'error' => 'Cannot load AI documents',
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    public function documents(): array
    {
        $config = $this->loadChatConfig();

        return array_merge(
            $this->guideDocuments($config),
            $this->categoryDocuments(),
            $this->productDocuments(),
            $this->promotionDocuments()
        );
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

    private function guideDocuments(array $config): array
    {
        $shop = $config['shop'] ?? [];
        $shopName = (string) ($shop['name'] ?? 'PC Parts Shop');
        $hotline = (string) ($shop['hotline'] ?? '');
        $hours = (string) ($shop['hours'] ?? '');
        $email = (string) ($shop['email'] ?? '');

        return [
            [
                'id' => 'guide_shop_info',
                'type' => 'guide',
                'title' => 'Thong tin shop va lien he',
                'text' => "{$shopName} ban linh kien may tinh, PC gaming va phu kien. Hotline: {$hotline}. Gio ho tro: {$hours}. Email: {$email}.",
                'url' => $this->absoluteUrl('/'),
                'metadata' => ['source' => 'website', 'section' => 'shop'],
            ],
            [
                'id' => 'guide_how_to_order',
                'type' => 'guide',
                'title' => 'Huong dan dat hang',
                'text' => 'Cach dat hang: chon san pham, bam Them vao gio hang, vao Gio hang kiem tra, bam Thanh toan, dang nhap neu can, nhap dia chi giao hang, chon phuong thuc thanh toan va dat hang. Khach co the xem lai don trong muc Don hang cua toi.',
                'url' => $this->absoluteUrl('/cart'),
                'metadata' => ['source' => 'website', 'section' => 'ordering'],
            ],
            [
                'id' => 'guide_build_pc',
                'type' => 'guide',
                'title' => 'Nguyen tac Build PC',
                'text' => 'Khi build PC can co du CPU, mainboard, RAM, VGA, nguon, case, SSD va tan nhiet. HDD la tuy chon. Khi tu van cau hinh, can kiem tra socket CPU voi mainboard, RAM type cua mainboard, cong suat nguon va ton kho san pham.',
                'url' => $this->absoluteUrl('/build-pc'),
                'metadata' => ['source' => 'website', 'section' => 'build_pc'],
            ],
            [
                'id' => 'guide_returns',
                'type' => 'guide',
                'title' => 'Hoan tra don hang',
                'text' => 'Khach hang co the yeu cau hoan tra doi voi don hang da hoan thanh trong trang chi tiet don hang. Khi hoan tra, so luong san pham duoc cong lai vao kho.',
                'url' => $this->absoluteUrl('/orders'),
                'metadata' => ['source' => 'website', 'section' => 'returns'],
            ],
        ];
    }

    private function categoryDocuments(): array
    {
        $stmt = Database::connection()->query('
            SELECT c.id, c.name, c.slug, COUNT(p.id) AS product_count
            FROM categories c
            LEFT JOIN products p ON p.category_id = c.id
            GROUP BY c.id, c.name, c.slug
            ORDER BY c.name ASC
        ');

        $documents = [];
        foreach ($stmt->fetchAll() as $row) {
            $documents[] = [
                'id' => 'category_' . (int) $row['id'],
                'type' => 'category',
                'title' => 'Danh muc ' . (string) $row['name'],
                'text' => 'Danh muc ' . (string) $row['name'] . ' co ' . (int) $row['product_count'] . ' san pham tren website. Slug danh muc: ' . (string) $row['slug'] . '.',
                'url' => $this->absoluteUrl('/?category=' . (int) $row['id']),
                'metadata' => [
                    'category_id' => (int) $row['id'],
                    'category_slug' => (string) $row['slug'],
                    'source' => 'website',
                ],
            ];
        }

        return $documents;
    }

    private function productDocuments(): array
    {
        $stmt = Database::connection()->query('
            SELECT p.id, p.name, p.price, p.stock_quantity, p.description,
                   p.socket, p.ram_type, p.vram_gb, p.wattage,
                   c.name AS category_name, c.slug AS category_slug
            FROM products p
            JOIN categories c ON c.id = p.category_id
            ORDER BY c.name ASC, p.id DESC
        ');

        $documents = [];
        foreach ($stmt->fetchAll() as $row) {
            $productId = (int) $row['id'];
            $price = (float) $row['price'];
            $flashSale = $this->activeFlashSaleForProduct($productId, $price);
            $saleText = '';
            if ($flashSale) {
                $saleText = ' Dang co flash sale: gia goc ' . number_format($price) . ' VND, gia giam ' . number_format((float) $flashSale['sale_price']) . ' VND, ket thuc ' . (string) ($flashSale['ends_at'] ?? 'khong ro') . '.';
            }

            $text = sprintf(
                "San pham: %s. Danh muc: %s (%s). Gia: %s VND.%s Ton kho: %d. Trang thai: %s. Mo ta/thong so: %s. Socket: %s. RAM type: %s. VRAM: %d GB. Wattage: %dW. Link chi tiet: %s",
                (string) $row['name'],
                (string) $row['category_name'],
                (string) $row['category_slug'],
                number_format($price),
                $saleText,
                (int) $row['stock_quantity'],
                (int) $row['stock_quantity'] > 0 ? 'con hang' : 'het hang',
                trim((string) ($row['description'] ?? '')),
                (string) ($row['socket'] ?? ''),
                (string) ($row['ram_type'] ?? ''),
                (int) ($row['vram_gb'] ?? 0),
                (int) ($row['wattage'] ?? 0),
                $this->absoluteUrl('/product?id=' . $productId)
            );

            $documents[] = [
                'id' => 'product_' . $productId,
                'type' => 'product',
                'title' => (string) $row['name'],
                'text' => $text,
                'url' => $this->absoluteUrl('/product?id=' . $productId),
                'metadata' => [
                    'product_id' => $productId,
                    'category_slug' => (string) $row['category_slug'],
                    'category_name' => (string) $row['category_name'],
                    'price' => $price,
                    'stock_quantity' => (int) $row['stock_quantity'],
                    'in_stock' => (int) $row['stock_quantity'] > 0,
                    'has_flash_sale' => $flashSale !== null,
                    'source' => 'website',
                ],
            ];
        }

        return $documents;
    }

    private function promotionDocuments(): array
    {
        $documents = [];

        if ($this->tableExists('coupons')) {
            $stmt = Database::connection()->query("
                SELECT *
                FROM coupons
                WHERE active = 1
                  AND (starts_at IS NULL OR starts_at <= NOW())
                  AND (ends_at IS NULL OR ends_at >= NOW())
                ORDER BY id DESC
            ");
            foreach ($stmt->fetchAll() as $row) {
                $documents[] = [
                    'id' => 'coupon_' . (int) $row['id'],
                    'type' => 'coupon',
                    'title' => 'Ma giam gia ' . (string) $row['code'],
                    'text' => 'Ma giam gia ' . (string) $row['code'] . ' dang hoat dong. Kieu giam: ' . (string) $row['discount_type'] . '. Gia tri giam: ' . number_format((float) $row['discount_value']) . '. Don toi thieu: ' . number_format((float) $row['min_order_amount']) . ' VND. Thoi gian ket thuc: ' . (string) ($row['ends_at'] ?? 'khong ro') . '.',
                    'url' => $this->absoluteUrl('/cart'),
                    'metadata' => ['source' => 'website', 'promotion_type' => 'coupon'],
                ];
            }
        }

        if ($this->tableExists('combo_promotions')) {
            $stmt = Database::connection()->query("
                SELECT cp.*, c1.name AS category_a_name, c2.name AS category_b_name
                FROM combo_promotions cp
                JOIN categories c1 ON c1.id = cp.category_a_id
                JOIN categories c2 ON c2.id = cp.category_b_id
                WHERE cp.active = 1
                ORDER BY cp.id DESC
            ");
            foreach ($stmt->fetchAll() as $row) {
                $documents[] = [
                    'id' => 'combo_' . (int) $row['id'],
                    'type' => 'combo_promotion',
                    'title' => (string) $row['name'],
                    'text' => 'Khuyen mai combo: ' . (string) $row['name'] . '. Mua cung luc danh muc ' . (string) $row['category_a_name'] . ' va ' . (string) $row['category_b_name'] . ' se duoc giam ' . number_format((float) $row['discount_amount']) . ' VND.',
                    'url' => $this->absoluteUrl('/cart'),
                    'metadata' => ['source' => 'website', 'promotion_type' => 'combo'],
                ];
            }
        }

        return $documents;
    }

    private function activeFlashSaleForProduct(int $productId, float $price): ?array
    {
        if (!$this->tableExists('flash_sales')) {
            return null;
        }

        $stmt = Database::connection()->prepare("
            SELECT *
            FROM flash_sales
            WHERE product_id = :product_id
              AND active = 1
              AND (starts_at IS NULL OR starts_at <= NOW())
              AND (ends_at IS NULL OR ends_at >= NOW())
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute(['product_id' => $productId]);
        $sale = $stmt->fetch();
        if (!$sale) {
            return null;
        }

        $discount = (string) $sale['discount_type'] === 'percent'
            ? $price * max(0, (float) $sale['discount_value']) / 100
            : max(0, (float) $sale['discount_value']);
        $sale['sale_price'] = max(0, $price - min($price, $discount));

        return $sale;
    }

    private function tableExists(string $table): bool
    {
        $stmt = Database::connection()->prepare('SHOW TABLES LIKE :table_name');
        $stmt->execute(['table_name' => $table]);
        return (bool) $stmt->fetch();
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
