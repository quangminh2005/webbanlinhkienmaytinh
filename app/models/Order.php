<?php

namespace App\Models;

use App\Core\Database;
use Exception;

class Order
{
    public function createFromCart(int $userId, array $cart, string $shippingAddress, string $paymentMethod, ?array $pricing = null): int
    {
        $db = Database::connection();
        $productModel = new Product();

        $shippingAddress = trim($shippingAddress);
        if ($shippingAddress === '') {
            throw new Exception('Dia chi giao hang rong.');
        }

        $paymentMethod = trim($paymentMethod);
        if (!in_array($paymentMethod, ['cod', 'bank_transfer'], true)) {
            throw new Exception('Phuong thuc thanh toan khong hop le.');
        }

        $items = [];
        $subtotal = 0.0;

        // Tinh tong va lay gia/so luong hien tai
        if ($pricing && !empty($pricing['items'])) {
            foreach ($pricing['items'] as $item) {
                $items[] = [
                    'product' => $item['product'],
                    'qty' => (int) $item['qty'],
                    'unit_price' => (float) $item['unit_price'],
                ];
                $subtotal += (float) $item['line_total'];
            }
        } else {
            foreach ($cart as $productId => $qty) {
                $productId = (int) $productId;
                $qty = (int) $qty;
                if ($productId <= 0 || $qty <= 0) {
                    continue;
                }

                $product = $productModel->find($productId);
                if (!$product) {
                    continue;
                }

                $items[] = ['product' => $product, 'qty' => $qty, 'unit_price' => (float) $product['price']];
                $subtotal += ((float) $product['price']) * $qty;
            }
        }

        if (empty($items)) {
            throw new Exception('Gio hang trong.');
        }

        $discountAmount = $pricing ? (float) ($pricing['discount_total'] ?? 0) : 0.0;
        $total = $pricing ? (float) ($pricing['total'] ?? max(0, $subtotal - $discountAmount)) : $subtotal;
        $couponCode = $pricing ? (string) ($pricing['coupon_code'] ?? '') : '';

        $db->beginTransaction();
        try {
            if ($this->ordersColumnExists('subtotal_amount')) {
                $stmt = $db->prepare(
                    'INSERT INTO orders (user_id, subtotal_amount, total_amount, discount_amount, coupon_code, status, shipping_address, payment_method, payment_status)
                     VALUES (:user_id, :subtotal_amount, :total_amount, :discount_amount, :coupon_code, :status, :shipping_address, :payment_method, :payment_status)'
                );

                $stmt->execute([
                    'user_id' => $userId,
                    'subtotal_amount' => $subtotal,
                    'total_amount' => $total,
                    'discount_amount' => $discountAmount,
                    'coupon_code' => $couponCode !== '' ? $couponCode : null,
                    'status' => 'pending',
                    'shipping_address' => $shippingAddress,
                    'payment_method' => $paymentMethod,
                    'payment_status' => 'unpaid',
                ]);
            } else {
                $stmt = $db->prepare(
                    'INSERT INTO orders (user_id, total_amount, status, shipping_address, payment_method, payment_status)
                     VALUES (:user_id, :total_amount, :status, :shipping_address, :payment_method, :payment_status)'
                );

                $stmt->execute([
                    'user_id' => $userId,
                    'total_amount' => $total,
                    'status' => 'pending',
                    'shipping_address' => $shippingAddress,
                    'payment_method' => $paymentMethod,
                    'payment_status' => 'unpaid',
                ]);
            }

            $orderId = (int) $db->lastInsertId();

            foreach ($items as $item) {
                $product = $item['product'];
                $qty = (int) $item['qty'];
                $productId = (int) $product['id'];
                $unitPrice = (float) ($item['unit_price'] ?? $product['price']);

                // Cong thuc tru ton kho an toan: chi tru neu con du.
                $upd = $db->prepare(
                    'UPDATE products
                     SET stock_quantity = stock_quantity - :qty
                     WHERE id = :id AND stock_quantity >= :qty'
                );
                $upd->execute(['qty' => $qty, 'id' => $productId]);

                if ($upd->rowCount() !== 1) {
                    throw new Exception('Khong du ton kho cho san pham: ' . $product['name']);
                }

                $ins = $db->prepare(
                    'INSERT INTO order_items (order_id, product_id, quantity, unit_price, unit_cost_price)
                     VALUES (:order_id, :product_id, :quantity, :unit_price, :unit_cost_price)'
                );
                $ins->execute([
                    'order_id' => $orderId,
                    'product_id' => $productId,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'unit_cost_price' => $product['cost_price'] ?? 0,
                ]);
            }

            $db->commit();
            return $orderId;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function findByIdForUser(int $id, int $userId): ?array
    {
        $db = Database::connection();
        $stmt = $db->prepare('
            SELECT o.*, u.name AS user_name
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.id = :id AND o.user_id = :user_id
            LIMIT 1
        ');
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        $order = $stmt->fetch();
        return $order ?: null;
    }

    public function listForUser(int $userId): array
    {
        $db = Database::connection();
        $stmt = $db->prepare('
            SELECT id, total_amount, status, created_at
            FROM orders
            WHERE user_id = :user_id
            ORDER BY created_at DESC
        ');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function returnForUser(int $orderId, int $userId): void
    {
        $db = Database::connection();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare('
                SELECT id, status
                FROM orders
                WHERE id = :id AND user_id = :user_id
                LIMIT 1
                FOR UPDATE
            ');
            $stmt->execute(['id' => $orderId, 'user_id' => $userId]);
            $order = $stmt->fetch();

            if (!$order) {
                throw new Exception('Khong tim thay don hang.');
            }

            if ((string) $order['status'] !== 'completed') {
                throw new Exception('Chi co the hoan tra don hang da hoan thanh.');
            }

            $this->restoreStockForOrder($orderId);
            $this->setStatus($orderId, 'returned');

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function findWithItemsForUser(int $orderId, int $userId): ?array
    {
        $db = Database::connection();
        $stmt = $db->prepare('
            SELECT o.*,
                   oi.product_id, oi.quantity, oi.unit_price,
                   p.name AS product_name
            FROM orders o
            JOIN order_items oi ON oi.order_id = o.id
            JOIN products p ON p.id = oi.product_id
            WHERE o.id = :order_id AND o.user_id = :user_id
            ORDER BY oi.id ASC
        ');
        $stmt->execute(['order_id' => $orderId, 'user_id' => $userId]);
        $rows = $stmt->fetchAll();
        if (!$rows) {
            return null;
        }

        $order = [
            'id' => (int) $rows[0]['id'],
            'total_amount' => $rows[0]['total_amount'],
            'status' => $rows[0]['status'],
            'shipping_address' => $rows[0]['shipping_address'],
            'payment_method' => $rows[0]['payment_method'] ?? null,
            'payment_status' => $rows[0]['payment_status'] ?? null,
            'created_at' => $rows[0]['created_at'],
            'items' => [],
        ];

        foreach ($rows as $row) {
            $order['items'][] = [
                'product_id' => (int) $row['product_id'],
                'product_name' => $row['product_name'],
                'quantity' => (int) $row['quantity'],
                'unit_price' => $row['unit_price'],
            ];
        }

        return $order;
    }

    public function findWithItemsForAdmin(int $orderId): ?array
    {
        $db = Database::connection();
        $phoneSelect = $this->usersColumnExists('phone') ? 'u.phone AS user_phone,' : "'' AS user_phone,";
        $addressSelect = $this->usersColumnExists('address') ? 'u.address AS user_address,' : "'' AS user_address,";

        $stmt = $db->prepare('
            SELECT o.*,
                   u.name AS user_name, u.email AS user_email, ' . $phoneSelect . ' ' . $addressSelect . '
                   oi.product_id, oi.quantity, oi.unit_price,
                   p.name AS product_name
            FROM orders o
            JOIN users u ON u.id = o.user_id
            JOIN order_items oi ON oi.order_id = o.id
            JOIN products p ON p.id = oi.product_id
            WHERE o.id = :order_id
            ORDER BY oi.id ASC
        ');
        $stmt->execute(['order_id' => $orderId]);
        $rows = $stmt->fetchAll();
        if (!$rows) {
            return null;
        }

        $order = [
            'id' => (int) $rows[0]['id'],
            'user_id' => (int) $rows[0]['user_id'],
            'user_name' => $rows[0]['user_name'],
            'user_email' => $rows[0]['user_email'],
            'user_phone' => $rows[0]['user_phone'] ?? '',
            'user_address' => $rows[0]['user_address'] ?? '',
            'total_amount' => $rows[0]['total_amount'],
            'status' => $rows[0]['status'],
            'shipping_address' => $rows[0]['shipping_address'],
            'payment_method' => $rows[0]['payment_method'] ?? null,
            'payment_status' => $rows[0]['payment_status'] ?? null,
            'created_at' => $rows[0]['created_at'],
            'items' => [],
        ];

        foreach ($rows as $row) {
            $order['items'][] = [
                'product_id' => (int) $row['product_id'],
                'product_name' => $row['product_name'],
                'quantity' => (int) $row['quantity'],
                'unit_price' => $row['unit_price'],
            ];
        }

        return $order;
    }

    private function usersColumnExists(string $column): bool
    {
        $stmt = Database::connection()->prepare('SHOW COLUMNS FROM users LIKE :column_name');
        $stmt->execute(['column_name' => $column]);
        return (bool) $stmt->fetch();
    }

    private function ordersColumnExists(string $column): bool
    {
        $stmt = Database::connection()->prepare('SHOW COLUMNS FROM orders LIKE :column_name');
        $stmt->execute(['column_name' => $column]);
        return (bool) $stmt->fetch();
    }

    public function listAll(?string $status): array
    {
        $db = Database::connection();
        $sql = 'SELECT id, user_id, total_amount, status, created_at FROM orders';
        $params = [];
        if ($status && $status !== '') {
            $sql .= ' WHERE status = :status';
            $params['status'] = $status;
        }
        $sql .= ' ORDER BY created_at DESC';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function updateStatus(int $orderId, string $status): void
    {
        $allowed = ['pending', 'processing', 'shipping', 'completed', 'cancelled', 'returned'];
        if (!in_array($status, $allowed, true)) {
            throw new Exception('Trang thai khong hop le.');
        }

        $db = Database::connection();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare('SELECT status FROM orders WHERE id = :id LIMIT 1 FOR UPDATE');
            $stmt->execute(['id' => $orderId]);
            $order = $stmt->fetch();
            if (!$order) {
                throw new Exception('Khong tim thay don hang.');
            }

            $currentStatus = (string) $order['status'];
            if ($currentStatus === 'returned' && $status !== 'returned') {
                throw new Exception('Don hang da hoan tra khong the doi sang trang thai khac.');
            }

            if ($status === 'returned' && $currentStatus !== 'returned') {
                if ($currentStatus !== 'completed') {
                    throw new Exception('Chi co the hoan tra don hang da hoan thanh.');
                }
                $this->restoreStockForOrder($orderId);
            }

            $this->setStatus($orderId, $status);
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    private function restoreStockForOrder(int $orderId): void
    {
        $db = Database::connection();
        $itemsStmt = $db->prepare('
            SELECT product_id, quantity
            FROM order_items
            WHERE order_id = :order_id
        ');
        $itemsStmt->execute(['order_id' => $orderId]);
        $items = $itemsStmt->fetchAll();

        if (!$items) {
            throw new Exception('Don hang khong co san pham de hoan tra.');
        }

        $stockStmt = $db->prepare('
            UPDATE products
            SET stock_quantity = stock_quantity + :quantity
            WHERE id = :product_id
        ');

        foreach ($items as $item) {
            $stockStmt->execute([
                'quantity' => (int) $item['quantity'],
                'product_id' => (int) $item['product_id'],
            ]);
        }
    }

    private function setStatus(int $orderId, string $status): void
    {
        $stmt = Database::connection()->prepare('UPDATE orders SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $orderId]);
    }
}

