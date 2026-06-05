<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Analytics;

class AdminDashboardController extends Controller
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
        $analytics = new Analytics();

        $this->view('admin/dashboard', [
            'summary' => $analytics->summary(),
            'topSellers' => $analytics->topSellers(),
            'deadstock' => $analytics->deadstock(),
        ]);
    }
}
