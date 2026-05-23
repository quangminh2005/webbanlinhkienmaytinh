<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Order;
use App\Models\Review;

class OrdersController extends Controller
{
    private function ensureLogin(): array
    {
        $user = $_SESSION['user'] ?? null;
        if (!$user) {
            $_SESSION['error'] = 'Vui long dang nhap truoc.';
            $this->redirect('/auth/login');
        }
        return $user;
    }

    public function index(): void
    {
        $user = $this->ensureLogin();
        $orders = (new Order())->listForUser((int) $user['id']);
        $this->view('orders/index', ['orders' => $orders]);
    }

    public function detail(): void
    {
        $user = $this->ensureLogin();
        $orderId = (int) ($_GET['id'] ?? 0);

        if ($orderId <= 0) {
            http_response_code(404);
            echo 'Not Found';
            return;
        }

        $orderModel = new Order();
        $order = $orderModel->findWithItemsForUser($orderId, (int) $user['id']);
        if (!$order) {
            http_response_code(404);
            echo 'Order not found';
            return;
        }

        $reviews = (new Review())->listByOrderId($orderId);
        $reviewsByProductId = [];
        foreach ($reviews as $review) {
            $reviewsByProductId[(int) $review['product_id']] = $review;
        }

        $this->view('orders/view', [
            'order' => $order,
            'reviews_by_product_id' => $reviewsByProductId,
        ]);
    }

    public function returnOrder(): void
    {
        $user = $this->ensureLogin();
        $orderId = (int) ($_POST['id'] ?? 0);

        if ($orderId <= 0) {
            $_SESSION['error'] = 'ID don khong hop le.';
            $this->redirect('/orders');
        }

        try {
            (new Order())->returnForUser($orderId, (int) $user['id']);
            $_SESSION['success'] = 'Yeu cau hoan tra thanh cong. So luong san pham da duoc cong lai vao kho.';
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        $this->redirect('/orders/view?id=' . $orderId);
    }
}

