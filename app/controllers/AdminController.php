<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Product;

class AdminController extends Controller
{
    private function ensureAdmin(): void
    {
        $user = $_SESSION['user'] ?? null;
        if (!$user || $user['role'] !== 'admin') {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }
    }

    public function products(): void
    {
        $this->ensureAdmin();
        $productModel = new Product();
        $keyword = trim((string) ($_GET['keyword'] ?? ''));
        $categoryId = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int) $_GET['category_id'] : null;
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 10;
        $total = $productModel->countForAdmin($keyword, $categoryId);
        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        $this->view('admin/products', [
            'products' => $productModel->paginatedForAdmin($keyword, $categoryId, $perPage, $offset),
            'categories' => $productModel->categories(),
            'filters' => [
                'keyword' => $keyword,
                'category_id' => $categoryId,
                'page' => $page,
                'total_pages' => $totalPages,
                'total' => $total,
            ],
        ]);
    }

    public function createProduct(): void
    {
        $this->ensureAdmin();
        $uploadedImage = $this->handleImageUpload($_FILES['image_file'] ?? null);
        $imageUrl = $uploadedImage ?: trim((string) ($_POST['image_url'] ?? ''));

        (new Product())->create([
            'category_id' => (int) $_POST['category_id'],
            'name' => trim($_POST['name']),
            'price' => (float) $_POST['price'],
            'cost_price' => (float) ($_POST['cost_price'] ?? 0),
            'stock_quantity' => (int) $_POST['stock_quantity'],
            'description' => trim($_POST['description']),
            'image_url' => $imageUrl,
            'socket' => trim($_POST['socket']),
            'ram_type' => trim($_POST['ram_type']),
            'vram_gb' => (int) ($_POST['vram_gb'] ?: 0),
            'wattage' => (int) ($_POST['wattage'] ?: 0),
        ]);
        $this->redirect('/admin/products');
    }

    public function updateProduct(): void
    {
        $this->ensureAdmin();
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['error'] = 'ID san pham khong hop le.';
            $this->redirect('/admin/products');
        }

        $uploadedImage = $this->handleImageUpload($_FILES['image_file'] ?? null);
        $postedImageUrl = trim((string) ($_POST['image_url'] ?? ''));
        $existingImageUrl = trim((string) ($_POST['existing_image_url'] ?? ''));
        $finalImageUrl = $uploadedImage ?: ($postedImageUrl !== '' ? $postedImageUrl : $existingImageUrl);

        (new Product())->update($id, [
            'category_id' => (int) $_POST['category_id'],
            'name' => trim($_POST['name']),
            'price' => (float) $_POST['price'],
            'cost_price' => (float) ($_POST['cost_price'] ?? 0),
            'stock_quantity' => (int) $_POST['stock_quantity'],
            'description' => trim($_POST['description']),
            'image_url' => $finalImageUrl,
            'socket' => trim($_POST['socket']),
            'ram_type' => trim($_POST['ram_type']),
            'vram_gb' => (int) ($_POST['vram_gb'] ?: 0),
            'wattage' => (int) ($_POST['wattage'] ?: 0),
        ]);
        $_SESSION['success'] = 'Cap nhat san pham thanh cong.';
        $this->redirect('/admin/products');
    }

    public function deleteProduct(): void
    {
        $this->ensureAdmin();
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            (new Product())->delete($id);
            $_SESSION['success'] = 'Da xoa san pham.';
        }
        $this->redirect('/admin/products');
    }

    public function importProducts(): void
    {
        $this->ensureAdmin();

        if (empty($_FILES['products_csv']) || $_FILES['products_csv']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'Upload file CSV that bai.';
            $this->redirect('/admin/products');
        }

        $tmpPath = $_FILES['products_csv']['tmp_name'];
        $handle = fopen($tmpPath, 'r');
        if ($handle === false) {
            $_SESSION['error'] = 'Khong mo duoc file CSV.';
            $this->redirect('/admin/products');
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            $_SESSION['error'] = 'File CSV rong hoac sai dinh dang.';
            $this->redirect('/admin/products');
        }

        $headerMap = [];
        foreach ($header as $index => $column) {
            $headerMap[strtolower(trim((string) $column))] = $index;
        }

        $required = ['name', 'category_slug', 'price', 'stock_quantity'];
        foreach ($required as $key) {
            if (!array_key_exists($key, $headerMap)) {
                fclose($handle);
                $_SESSION['error'] = 'CSV thieu cot bat buoc: ' . $key;
                $this->redirect('/admin/products');
            }
        }

        $productModel = new Product();
        $success = 0;
        $failed = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $name = trim((string) $this->csvValue($row, $headerMap, 'name'));
            $categorySlug = $this->slugify((string) $this->csvValue($row, $headerMap, 'category_slug'));
            $categoryName = trim((string) $this->csvValue($row, $headerMap, 'category_name'));
            $price = (float) $this->csvValue($row, $headerMap, 'price');
            $costPrice = (float) $this->csvValue($row, $headerMap, 'cost_price');
            $stock = (int) $this->csvValue($row, $headerMap, 'stock_quantity');

            if ($name === '' || $categorySlug === '') {
                $failed++;
                continue;
            }

            $category = $productModel->findCategoryBySlug($categorySlug);
            $categoryId = $category
                ? (int) $category['id']
                : $productModel->createCategory(
                    $categoryName !== '' ? $categoryName : strtoupper($categorySlug),
                    $categorySlug
                );

            try {
                $productModel->create([
                    'category_id' => $categoryId,
                    'name' => $name,
                    'price' => $price,
                    'cost_price' => $costPrice,
                    'stock_quantity' => $stock,
                    'description' => trim((string) $this->csvValue($row, $headerMap, 'description')),
                    'image_url' => trim((string) $this->csvValue($row, $headerMap, 'image_url')),
                    'socket' => trim((string) $this->csvValue($row, $headerMap, 'socket')),
                    'ram_type' => trim((string) $this->csvValue($row, $headerMap, 'ram_type')),
                    'vram_gb' => (int) $this->csvValue($row, $headerMap, 'vram_gb'),
                    'wattage' => (int) $this->csvValue($row, $headerMap, 'wattage'),
                ]);
                $success++;
            } catch (\Throwable $e) {
                $failed++;
            }
        }

        fclose($handle);

        $_SESSION['success'] = "Import xong: {$success} san pham, loi: {$failed}.";
        $this->redirect('/admin/products');
    }

    private function csvValue(array $row, array $headerMap, string $column): string
    {
        if (!isset($headerMap[$column])) {
            return '';
        }
        $index = (int) $headerMap[$column];
        return isset($row[$index]) ? (string) $row[$index] : '';
    }

    private function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);
        return trim((string) $value, '-');
    }

    private function handleImageUpload(?array $file): string
    {
        if (!$file || !isset($file['error']) || (int) $file['error'] === UPLOAD_ERR_NO_FILE) {
            return '';
        }

        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'Upload anh that bai.';
            $this->redirect('/admin/products');
        }

        $mime = mime_content_type((string) $file['tmp_name']);
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];

        if (!isset($allowed[$mime])) {
            $_SESSION['error'] = 'Chi ho tro anh JPG, PNG, WEBP, GIF.';
            $this->redirect('/admin/products');
        }

        $uploadDir = __DIR__ . '/../../public/uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = 'product_' . time() . '_' . bin2hex(random_bytes(5)) . '.' . $allowed[$mime];
        $target = $uploadDir . '/' . $fileName;

        if (!move_uploaded_file((string) $file['tmp_name'], $target)) {
            $_SESSION['error'] = 'Khong the luu file anh.';
            $this->redirect('/admin/products');
        }

        return '/uploads/' . $fileName;
    }
}

