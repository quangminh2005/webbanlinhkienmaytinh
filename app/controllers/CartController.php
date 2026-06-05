<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Promotion;

class CartController extends Controller
{
    public function index(): void
    {
        $cart = $_SESSION['cart'] ?? [];
        $userId = isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : null;
        $pricing = (new Promotion())->calculateCart($cart, $userId, (string) ($_SESSION['coupon_code'] ?? ''));

        $this->view('cart/index', [
            'items' => $pricing['items'],
            'pricing' => $pricing,
        ]);
    }

    public function add(): void
    {
        $productId = (int) ($_POST['product_id'] ?? 0);
        if ($productId > 0) {
            if (!isset($_SESSION['cart'][$productId])) {
                $_SESSION['cart'][$productId] = 0;
            }
            $_SESSION['cart'][$productId]++;
        }

        $this->redirect('/cart');
    }

    public function applyCoupon(): void
    {
        $code = strtoupper(trim((string) ($_POST['coupon_code'] ?? '')));
        if ($code === '') {
            unset($_SESSION['coupon_code']);
        } else {
            $_SESSION['coupon_code'] = $code;
        }

        $this->redirect('/cart');
    }

    public function remove(): void
    {
        $productId = (int) ($_POST['product_id'] ?? 0);
        unset($_SESSION['cart'][$productId]);
        $this->redirect('/cart');
    }
}

