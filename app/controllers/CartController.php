<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Product;

class CartController extends Controller
{
    public function index(): void
    {
        $cart = $_SESSION['cart'] ?? [];
        $items = [];
        $total = 0;
        $productModel = new Product();

        foreach ($cart as $productId => $qty) {
            $product = $productModel->find((int) $productId);
            if (!$product) {
                continue;
            }
            $lineTotal = $product['price'] * $qty;
            $total += $lineTotal;
            $items[] = ['product' => $product, 'qty' => $qty, 'line_total' => $lineTotal];
        }

        $this->view('cart/index', ['items' => $items, 'total' => $total]);
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

    public function remove(): void
    {
        $productId = (int) ($_POST['product_id'] ?? 0);
        unset($_SESSION['cart'][$productId]);
        $this->redirect('/cart');
    }
}

