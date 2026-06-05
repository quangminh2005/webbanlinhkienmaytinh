<?php

namespace App\Models;

use App\Core\Database;

class Analytics
{
    public function summary(): array
    {
        $stmt = Database::connection()->query("
            SELECT
                COUNT(o.id) AS order_count,
                COALESCE(SUM(o.total_amount), 0) AS revenue,
                COALESCE(SUM(order_cost.cost), 0) AS cost,
                COALESCE(SUM(o.total_amount - order_cost.cost), 0) AS profit
            FROM orders o
            JOIN (
                SELECT
                    oi.order_id,
                    SUM(oi.quantity * CASE WHEN oi.unit_cost_price > 0 THEN oi.unit_cost_price ELSE p.cost_price END) AS cost
                FROM order_items oi
                JOIN products p ON p.id = oi.product_id
                GROUP BY oi.order_id
            ) order_cost ON order_cost.order_id = o.id
            WHERE o.status = 'completed'
        ");

        $row = $stmt->fetch() ?: [];
        $inventoryStmt = Database::connection()->query("
            SELECT COALESCE(SUM(stock_quantity * cost_price), 0) AS inventory_cost
            FROM products
            WHERE stock_quantity > 0
        ");
        $inventoryRow = $inventoryStmt->fetch() ?: [];

        return [
            'order_count' => (int) ($row['order_count'] ?? 0),
            'revenue' => (float) ($row['revenue'] ?? 0),
            'cost' => (float) ($row['cost'] ?? 0),
            'profit' => (float) ($row['profit'] ?? 0),
            'inventory_cost' => (float) ($inventoryRow['inventory_cost'] ?? 0),
        ];
    }

    public function topSellers(int $limit = 10): array
    {
        $stmt = Database::connection()->prepare("
            SELECT
                p.id,
                p.name,
                c.name AS category_name,
                SUM(oi.quantity) AS sold_quantity,
                SUM(oi.quantity * oi.unit_price) AS revenue,
                SUM(oi.quantity * (oi.unit_price - CASE WHEN oi.unit_cost_price > 0 THEN oi.unit_cost_price ELSE p.cost_price END)) AS profit
            FROM order_items oi
            JOIN orders o ON o.id = oi.order_id
            JOIN products p ON p.id = oi.product_id
            JOIN categories c ON c.id = p.category_id
            WHERE o.status = 'completed'
            GROUP BY p.id, p.name, c.name
            ORDER BY sold_quantity DESC, revenue DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function deadstock(int $limit = 15): array
    {
        $stmt = Database::connection()->prepare("
            SELECT
                p.id,
                p.name,
                c.name AS category_name,
                p.stock_quantity,
                p.price,
                p.cost_price,
                p.created_at,
                COALESCE(SUM(CASE WHEN o.status = 'completed' THEN oi.quantity ELSE 0 END), 0) AS sold_quantity,
                DATEDIFF(CURRENT_DATE, DATE(p.created_at)) AS days_in_stock
            FROM products p
            JOIN categories c ON c.id = p.category_id
            LEFT JOIN order_items oi ON oi.product_id = p.id
            LEFT JOIN orders o ON o.id = oi.order_id
            WHERE p.stock_quantity > 0
            GROUP BY p.id, p.name, c.name, p.stock_quantity, p.price, p.cost_price, p.created_at
            HAVING sold_quantity = 0
            ORDER BY days_in_stock DESC, p.stock_quantity DESC, p.id ASC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
