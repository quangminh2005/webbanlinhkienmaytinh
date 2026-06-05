<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Product;
use App\Models\Promotion;

class AdminPromotionsController extends Controller
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

    public function index(): void
    {
        $this->ensureAdmin();
        $promotion = new Promotion();
        $product = new Product();

        $this->view('admin/promotions', [
            'coupons' => $promotion->coupons(),
            'combos' => $promotion->combos(),
            'flashSales' => $promotion->flashSales(),
            'categories' => $product->categories(),
            'products' => $product->all(),
        ]);
    }

    public function createCoupon(): void
    {
        $this->ensureAdmin();
        (new Promotion())->createCoupon([
            'code' => strtoupper(trim((string) ($_POST['code'] ?? ''))),
            'discount_type' => $_POST['discount_type'] === 'fixed' ? 'fixed' : 'percent',
            'discount_value' => (float) ($_POST['discount_value'] ?? 0),
            'min_order_amount' => (float) ($_POST['min_order_amount'] ?? 0),
            'starts_at' => $this->dateValue('starts_at'),
            'ends_at' => $this->dateValue('ends_at'),
            'active' => isset($_POST['active']) ? 1 : 0,
        ]);
        $_SESSION['success'] = 'Da tao ma giam gia.';
        $this->redirect('/admin/promotions');
    }

    public function createCombo(): void
    {
        $this->ensureAdmin();
        (new Promotion())->createCombo([
            'name' => trim((string) ($_POST['name'] ?? '')),
            'category_a_id' => (int) ($_POST['category_a_id'] ?? 0),
            'category_b_id' => (int) ($_POST['category_b_id'] ?? 0),
            'discount_amount' => (float) ($_POST['discount_amount'] ?? 0),
            'active' => isset($_POST['active']) ? 1 : 0,
        ]);
        $_SESSION['success'] = 'Da tao khuyen mai combo.';
        $this->redirect('/admin/promotions');
    }

    public function createFlashSale(): void
    {
        $this->ensureAdmin();
        (new Promotion())->createFlashSale([
            'product_id' => (int) ($_POST['product_id'] ?? 0),
            'discount_type' => $_POST['discount_type'] === 'fixed' ? 'fixed' : 'percent',
            'discount_value' => (float) ($_POST['discount_value'] ?? 0),
            'starts_at' => $this->dateValue('starts_at'),
            'ends_at' => $this->dateValue('ends_at'),
            'active' => isset($_POST['active']) ? 1 : 0,
        ]);
        $_SESSION['success'] = 'Da tao flash sale.';
        $this->redirect('/admin/promotions');
    }

    public function delete(): void
    {
        $this->ensureAdmin();
        $type = (string) ($_POST['type'] ?? '');
        $table = [
            'coupon' => 'coupons',
            'combo' => 'combo_promotions',
            'flash' => 'flash_sales',
        ][$type] ?? '';

        (new Promotion())->delete($table, (int) ($_POST['id'] ?? 0));
        $_SESSION['success'] = 'Da xoa khuyen mai.';
        $this->redirect('/admin/promotions');
    }

    private function dateValue(string $key): ?string
    {
        $value = trim((string) ($_POST[$key] ?? ''));
        return $value !== '' ? str_replace('T', ' ', $value) . ':00' : null;
    }
}
