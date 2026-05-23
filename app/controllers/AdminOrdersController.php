<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Order;
use App\Models\Review;

class AdminOrdersController extends Controller
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

    public function orders(): void
    {
        $this->ensureAdmin();
        $status = isset($_GET['status']) ? trim((string) $_GET['status']) : '';
        $orders = (new Order())->listAll($status !== '' ? $status : null);
        $this->view('admin/orders', [
            'orders' => $orders,
            'status_filter' => $status,
            'statuses' => ['pending', 'processing', 'shipping', 'completed', 'cancelled', 'returned'],
        ]);
    }

    public function updateStatus(): void
    {
        $this->ensureAdmin();
        $orderId = (int) ($_POST['id'] ?? 0);
        $status = trim((string) ($_POST['status'] ?? ''));
        if ($orderId <= 0) {
            $_SESSION['error'] = 'ID don khong hop le.';
            $this->redirect('/admin/orders');
        }

        try {
            (new Order())->updateStatus($orderId, $status);
            $_SESSION['success'] = 'Cap nhat trang thai don thanh cong.';
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        $this->redirect('/admin/orders');
    }

    public function detail(): void
    {
        $this->ensureAdmin();
        $orderId = (int) ($_GET['id'] ?? 0);

        if ($orderId <= 0) {
            http_response_code(404);
            echo 'Not Found';
            return;
        }

        $order = (new Order())->findWithItemsForAdmin($orderId);
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

        $this->view('admin/order_detail', [
            'order' => $order,
            'reviews_by_product_id' => $reviewsByProductId,
        ]);
    }
}

