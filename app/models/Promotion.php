<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Promotion
{
    private array $tiers = [
        'Kim Cuong' => ['min' => 100000000, 'percent' => 8],
        'Vang' => ['min' => 50000000, 'percent' => 5],
        'Bac' => ['min' => 10000000, 'percent' => 2],
    ];

    public function calculateCart(array $cart, ?int $userId = null, string $couponCode = ''): array
    {
        $productModel = new Product();
        $items = [];
        $subtotal = 0.0;

        foreach ($cart as $productId => $qty) {
            $product = $productModel->find((int) $productId);
            $qty = (int) $qty;
            if (!$product || $qty <= 0) {
                continue;
            }

            $originalPrice = (float) $product['price'];
            $flash = $this->activeFlashSaleForProduct((int) $product['id']);
            $unitPrice = $flash ? $this->discountedPrice($originalPrice, $flash['discount_type'], (float) $flash['discount_value']) : $originalPrice;
            $lineTotal = $unitPrice * $qty;
            $subtotal += $lineTotal;

            $items[] = [
                'product' => $product,
                'qty' => $qty,
                'original_unit_price' => $originalPrice,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
                'flash_sale' => $flash,
            ];
        }

        $discounts = [];
        $comboDiscount = $this->comboDiscount($items);
        if ($comboDiscount > 0) {
            $discounts[] = ['label' => 'Giam gia combo', 'amount' => $comboDiscount];
        }

        $coupon = $couponCode !== '' ? $this->findActiveCoupon($couponCode, $subtotal) : null;
        if ($coupon) {
            $couponDiscount = $this->discountAmount($subtotal, (string) $coupon['discount_type'], (float) $coupon['discount_value']);
            if ($couponDiscount > 0) {
                $discounts[] = ['label' => 'Ma giam gia ' . $coupon['code'], 'amount' => $couponDiscount];
            }
        }

        $tier = $userId ? $this->membershipTier($userId) : ['name' => 'Chua dang nhap', 'percent' => 0, 'total_spent' => 0];
        $afterPromo = max(0, $subtotal - array_sum(array_column($discounts, 'amount')));
        $tierDiscount = $afterPromo * ((float) $tier['percent'] / 100);
        if ($tierDiscount > 0) {
            $discounts[] = ['label' => 'Uu dai thanh vien ' . $tier['name'], 'amount' => $tierDiscount];
        }

        $discountTotal = min($subtotal, array_sum(array_column($discounts, 'amount')));

        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'discounts' => $discounts,
            'discount_total' => $discountTotal,
            'total' => max(0, $subtotal - $discountTotal),
            'coupon' => $coupon,
            'coupon_code' => $coupon ? (string) $coupon['code'] : '',
            'tier' => $tier,
        ];
    }

    public function membershipTier(int $userId): array
    {
        $stmt = Database::connection()->prepare("
            SELECT COALESCE(SUM(total_amount), 0) AS total_spent
            FROM orders
            WHERE user_id = :user_id AND status = 'completed'
        ");
        $stmt->execute(['user_id' => $userId]);
        $totalSpent = (float) (($stmt->fetch()['total_spent'] ?? 0));

        foreach ($this->tiers as $name => $tier) {
            if ($totalSpent >= $tier['min']) {
                return ['name' => $name, 'percent' => $tier['percent'], 'total_spent' => $totalSpent];
            }
        }

        return ['name' => 'Thuong', 'percent' => 0, 'total_spent' => $totalSpent];
    }

    public function coupons(): array
    {
        if (!$this->tableExists('coupons')) {
            return [];
        }
        return Database::connection()->query('SELECT * FROM coupons ORDER BY id DESC')->fetchAll();
    }

    public function combos(): array
    {
        if (!$this->tableExists('combo_promotions')) {
            return [];
        }
        return Database::connection()->query("
            SELECT cp.*, c1.name AS category_a_name, c2.name AS category_b_name
            FROM combo_promotions cp
            JOIN categories c1 ON c1.id = cp.category_a_id
            JOIN categories c2 ON c2.id = cp.category_b_id
            ORDER BY cp.id DESC
        ")->fetchAll();
    }

    public function flashSales(): array
    {
        if (!$this->tableExists('flash_sales')) {
            return [];
        }
        return Database::connection()->query("
            SELECT fs.*, p.name AS product_name
            FROM flash_sales fs
            JOIN products p ON p.id = fs.product_id
            ORDER BY fs.id DESC
        ")->fetchAll();
    }

    public function activeFlashSaleProducts(int $limit = 8): array
    {
        if (!$this->tableExists('flash_sales')) {
            return [];
        }

        $stmt = Database::connection()->prepare("
            SELECT
                fs.*,
                p.id AS product_id,
                p.name AS product_name,
                p.price AS original_price,
                p.stock_quantity,
                p.image_url,
                c.name AS category_name
            FROM flash_sales fs
            JOIN products p ON p.id = fs.product_id
            JOIN categories c ON c.id = p.category_id
            WHERE fs.active = 1
              AND (fs.starts_at IS NULL OR fs.starts_at <= NOW())
              AND (fs.ends_at IS NULL OR fs.ends_at >= NOW())
            ORDER BY fs.ends_at IS NULL ASC, fs.ends_at ASC, fs.id DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $sales = [];
        foreach ($stmt->fetchAll() as $sale) {
            $originalPrice = (float) $sale['original_price'];
            $sale['sale_price'] = $this->discountedPrice($originalPrice, (string) $sale['discount_type'], (float) $sale['discount_value']);
            $sale['saved_amount'] = max(0, $originalPrice - (float) $sale['sale_price']);
            $sales[] = $sale;
        }

        return $sales;
    }

    public function activeFlashSaleForProductDetail(int $productId, float $originalPrice): ?array
    {
        $sale = $this->activeFlashSaleForProduct($productId);
        if (!$sale) {
            return null;
        }

        $sale['original_price'] = $originalPrice;
        $sale['sale_price'] = $this->discountedPrice($originalPrice, (string) $sale['discount_type'], (float) $sale['discount_value']);
        $sale['saved_amount'] = max(0, $originalPrice - (float) $sale['sale_price']);

        return $sale;
    }

    public function createCoupon(array $data): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO coupons (code, discount_type, discount_value, min_order_amount, starts_at, ends_at, active)
             VALUES (:code, :discount_type, :discount_value, :min_order_amount, :starts_at, :ends_at, :active)'
        );
        $stmt->execute($data);
    }

    public function createCombo(array $data): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO combo_promotions (name, category_a_id, category_b_id, discount_amount, active)
             VALUES (:name, :category_a_id, :category_b_id, :discount_amount, :active)'
        );
        $stmt->execute($data);
    }

    public function createFlashSale(array $data): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO flash_sales (product_id, discount_type, discount_value, starts_at, ends_at, active)
             VALUES (:product_id, :discount_type, :discount_value, :starts_at, :ends_at, :active)'
        );
        $stmt->execute($data);
    }

    public function delete(string $table, int $id): void
    {
        if (!in_array($table, ['coupons', 'combo_promotions', 'flash_sales'], true)) {
            return;
        }
        $stmt = Database::connection()->prepare("DELETE FROM {$table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    private function activeFlashSaleForProduct(int $productId): ?array
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
        return $sale ?: null;
    }

    private function findActiveCoupon(string $code, float $subtotal): ?array
    {
        if (!$this->tableExists('coupons')) {
            return null;
        }
        $stmt = Database::connection()->prepare("
            SELECT *
            FROM coupons
            WHERE code = :code
              AND active = 1
              AND min_order_amount <= :subtotal
              AND (starts_at IS NULL OR starts_at <= NOW())
              AND (ends_at IS NULL OR ends_at >= NOW())
            LIMIT 1
        ");
        $stmt->execute(['code' => strtoupper(trim($code)), 'subtotal' => $subtotal]);
        $coupon = $stmt->fetch();
        return $coupon ?: null;
    }

    private function comboDiscount(array $items): float
    {
        if (empty($items) || !$this->tableExists('combo_promotions')) {
            return 0.0;
        }

        $categoryIds = [];
        foreach ($items as $item) {
            $categoryIds[] = (int) $item['product']['category_id'];
        }

        $stmt = Database::connection()->query('SELECT * FROM combo_promotions WHERE active = 1 ORDER BY id DESC');
        $discount = 0.0;
        foreach ($stmt->fetchAll() as $combo) {
            if (in_array((int) $combo['category_a_id'], $categoryIds, true) && in_array((int) $combo['category_b_id'], $categoryIds, true)) {
                $discount += (float) $combo['discount_amount'];
            }
        }

        return $discount;
    }

    private function discountedPrice(float $price, string $type, float $value): float
    {
        return max(0, $price - $this->discountAmount($price, $type, $value));
    }

    private function discountAmount(float $base, string $type, float $value): float
    {
        if ($type === 'percent') {
            return min($base, $base * max(0, $value) / 100);
        }

        return min($base, max(0, $value));
    }

    private function tableExists(string $table): bool
    {
        $stmt = Database::connection()->prepare('SHOW TABLES LIKE :table_name');
        $stmt->execute(['table_name' => $table]);
        return (bool) $stmt->fetch();
    }
}
