<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;

class ReviewsController extends Controller
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

    public function create(): void
    {
        $user = $this->ensureLogin();

        $orderId = (int) ($_POST['order_id'] ?? 0);
        $productId = (int) ($_POST['product_id'] ?? 0);
        $rating = (int) ($_POST['rating'] ?? 0);
        $comment = (string) ($_POST['comment'] ?? '');

        if ($orderId <= 0 || $productId <= 0 || $rating <= 0) {
            $_SESSION['error'] = 'Du lieu khong hop le.';
            $this->redirect('/orders');
        }

        $orderModel = new Order();
        $order = $orderModel->findWithItemsForUser($orderId, (int) $user['id']);
        if (!$order) {
            http_response_code(404);
            echo 'Order not found';
            return;
        }

        if ($order['status'] !== 'completed') {
            $_SESSION['error'] = 'Chi duoc review khi don da hoan thanh.';
            $this->redirect('/orders/view?id=' . $orderId);
        }

        // Kiem tra product co thuoc order
        $foundInOrder = false;
        foreach ($order['items'] as $item) {
            if ((int) $item['product_id'] === $productId) {
                $foundInOrder = true;
                break;
            }
        }
        if (!$foundInOrder) {
            $_SESSION['error'] = 'San pham khong thuoc don hang.';
            $this->redirect('/orders/view?id=' . $orderId);
        }

        $reviewModel = new Review();
        if ($reviewModel->hasReviewed((int) $user['id'], $orderId, $productId)) {
            $_SESSION['error'] = 'Ban da review san pham nay.';
            $this->redirect('/orders/view?id=' . $orderId);
        }

        // Rating se duoc clamp trong Review::create
        $reviewModel->create((int) $user['id'], $orderId, $productId, $rating, $comment);

        $_SESSION['success'] = 'Cam on ban da review!';
        $this->redirect('/orders/view?id=' . $orderId);
    }
}

