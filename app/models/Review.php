<?php

namespace App\Models;

use App\Core\Database;

class Review
{
    public function hasReviewed(int $userId, int $orderId, int $productId): bool
    {
        $db = Database::connection();
        $stmt = $db->prepare('
            SELECT 1
            FROM reviews
            WHERE user_id = :user_id AND order_id = :order_id AND product_id = :product_id
            LIMIT 1
        ');
        $stmt->execute([
            'user_id' => $userId,
            'order_id' => $orderId,
            'product_id' => $productId,
        ]);
        return (bool) $stmt->fetch();
    }

    public function create(int $userId, int $orderId, int $productId, int $rating, string $comment): void
    {
        $rating = max(1, min(5, $rating));
        $comment = trim($comment);

        $db = Database::connection();
        $stmt = $db->prepare(
            'INSERT INTO reviews (user_id, order_id, product_id, rating, comment)
             VALUES (:user_id, :order_id, :product_id, :rating, :comment)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'order_id' => $orderId,
            'product_id' => $productId,
            'rating' => $rating,
            'comment' => $comment,
        ]);
    }

    public function listByOrderId(int $orderId): array
    {
        $db = Database::connection();
        $stmt = $db->prepare('
            SELECT r.*, p.name AS product_name
            FROM reviews r
            JOIN products p ON p.id = r.product_id
            WHERE r.order_id = :order_id
            ORDER BY r.created_at DESC
        ');
        $stmt->execute(['order_id' => $orderId]);
        return $stmt->fetchAll();
    }
}

