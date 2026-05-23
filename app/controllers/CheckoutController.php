<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Order;
use App\Models\Product;

class CheckoutController extends Controller
{
    private function ensureLogin(): array
    {
        $user = $_SESSION['user'] ?? null;
        if (!$user) {
            $_SESSION['error'] = 'Vui long dang nhap truoc khi thanh toan.';
            $this->redirect('/auth/login');
        }
        return $user;
    }

    public function form(): void
    {
        $user = $this->ensureLogin();

        $cart = $_SESSION['cart'] ?? [];
        $items = [];
        $total = 0.0;
        $productModel = new Product();

        foreach ($cart as $productId => $qty) {
            $product = $productModel->find((int) $productId);
            if (!$product) {
                continue;
            }
            $qty = (int) $qty;
            if ($qty <= 0) {
                continue;
            }

            $lineTotal = ((float) $product['price']) * $qty;
            $total += $lineTotal;
            $items[] = ['product' => $product, 'qty' => $qty, 'line_total' => $lineTotal];
        }

        if (empty($items)) {
            $_SESSION['error'] = 'Gio hang trong.';
            $this->redirect('/cart');
        }

        $this->view('checkout/index', [
            'items' => $items,
            'total' => $total,
            'user' => $user,
        ]);
    }

    public function submit(): void
    {
        $user = $this->ensureLogin();

        $shippingAddress = trim((string) ($_POST['shipping_address'] ?? ''));
        $paymentMethod = trim((string) ($_POST['payment_method'] ?? 'cod'));

        $cart = $_SESSION['cart'] ?? [];
        if (empty($cart)) {
            $_SESSION['error'] = 'Gio hang trong.';
            $this->redirect('/cart');
        }

        try {
            $orderModel = new Order();
            $orderId = $orderModel->createFromCart((int) $user['id'], $cart, $shippingAddress, $paymentMethod);

            unset($_SESSION['cart']);

            $order = $orderModel->findByIdForUser($orderId, (int) $user['id']);
            if (!$order) {
                // Truong hop hiem: order khong lay duoc ngay.
                $this->view('checkout/success', [
                    'order_id' => $orderId,
                    'shipping_address' => $shippingAddress,
                    'payment_method' => $paymentMethod,
                ]);
                return;
            }

            $this->view('checkout/success', [
                'order' => $order,
                'payment_method' => $order['payment_method'] ?? $paymentMethod,
            ]);
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/checkout');
        }
    }
}

