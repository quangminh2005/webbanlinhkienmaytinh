<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Product
{
    public function paginatedForAdmin(string $keyword, ?int $categoryId, int $limit, int $offset): array
    {
        $db = Database::connection();
        $sql = 'SELECT p.*, c.name AS category_name
                FROM products p
                JOIN categories c ON p.category_id = c.id
                WHERE 1=1';
        $params = [];

        if ($keyword !== '') {
            $sql .= ' AND p.name LIKE :keyword';
            $params['keyword'] = '%' . $keyword . '%';
        }

        if ($categoryId) {
            $sql .= ' AND p.category_id = :category_id';
            $params['category_id'] = $categoryId;
        }

        $sql .= ' ORDER BY p.id DESC LIMIT :limit OFFSET :offset';
        $stmt = $db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countForAdmin(string $keyword, ?int $categoryId): int
    {
        $db = Database::connection();
        $sql = 'SELECT COUNT(*) AS total FROM products p WHERE 1=1';
        $params = [];

        if ($keyword !== '') {
            $sql .= ' AND p.name LIKE :keyword';
            $params['keyword'] = '%' . $keyword . '%';
        }

        if ($categoryId) {
            $sql .= ' AND p.category_id = :category_id';
            $params['category_id'] = $categoryId;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return (int) ($result['total'] ?? 0);
    }

    public function all(?int $categoryId = null, string $keyword = ''): array
    {
        $db = Database::connection();
        $sql = 'SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE 1=1';
        $params = [];

        if ($categoryId) {
            $sql .= ' AND p.category_id = :category_id';
            $params['category_id'] = $categoryId;
        }

        if ($keyword !== '') {
            $sql .= ' AND (p.name LIKE :kw OR p.description LIKE :kw)';
            $params['kw'] = '%' . $keyword . '%';
        }

        $sql .= ' ORDER BY p.id DESC';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT * FROM products WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $product = $stmt->fetch();
        return $product ?: null;
    }

    public function byCategorySlug(string $slug): array
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT p.* FROM products p
             JOIN categories c ON p.category_id = c.id
             WHERE c.slug = :slug'
        );
        $stmt->execute(['slug' => $slug]);
        return $stmt->fetchAll();
    }

    public function categories(): array
    {
        return Database::connection()->query('SELECT * FROM categories ORDER BY name')->fetchAll();
    }

    public function create(array $data): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO products (category_id, name, price, cost_price, stock_quantity, description, image_url, socket, ram_type, vram_gb, wattage)
             VALUES (:category_id, :name, :price, :cost_price, :stock_quantity, :description, :image_url, :socket, :ram_type, :vram_gb, :wattage)'
        );
        $stmt->execute($data);
    }

    public function update(int $id, array $data): void
    {
        $existing = $this->find($id);
        if ($existing) {
            $oldStock = (int) ($existing['stock_quantity'] ?? 0);
            $newStock = (int) ($data['stock_quantity'] ?? 0);
            $oldCost = (float) ($existing['cost_price'] ?? 0);
            $newCost = (float) ($data['cost_price'] ?? 0);

            if ($newStock > $oldStock && $newCost > 0) {
                $addedStock = $newStock - $oldStock;
                $data['cost_price'] = $oldStock > 0 && $oldCost > 0
                    ? (($oldStock * $oldCost) + ($addedStock * $newCost)) / $newStock
                    : $newCost;
            }
        }

        $data['id'] = $id;
        $stmt = Database::connection()->prepare(
            'UPDATE products
             SET category_id = :category_id,
                 name = :name,
                 price = :price,
                 cost_price = :cost_price,
                 stock_quantity = :stock_quantity,
                 description = :description,
                 image_url = :image_url,
                 socket = :socket,
                 ram_type = :ram_type,
                 vram_gb = :vram_gb,
                 wattage = :wattage
             WHERE id = :id'
        );
        $stmt->execute($data);
    }

    public function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM products WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function findCategoryBySlug(string $slug): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM categories WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        $category = $stmt->fetch();
        return $category ?: null;
    }

    public function createCategory(string $name, string $slug): int
    {
        $stmt = Database::connection()->prepare('INSERT INTO categories (name, slug) VALUES (:name, :slug)');
        $stmt->execute(['name' => $name, 'slug' => $slug]);
        return (int) Database::connection()->lastInsertId();
    }
}

