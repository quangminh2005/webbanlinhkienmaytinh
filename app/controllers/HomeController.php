<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Product;
use App\Models\Promotion;

class HomeController extends Controller
{
    public function index(): void
    {
        $productModel = new Product();
        $categoryId = isset($_GET['category']) ? (int) $_GET['category'] : null;
        $keyword = trim((string) ($_GET['q'] ?? ''));

        $this->view('home/index', [
            'products' => $productModel->all($categoryId, $keyword),
            'categories' => $productModel->categories(),
            'flashSales' => (new Promotion())->activeFlashSaleProducts(),
            'selectedCategory' => $categoryId,
            'searchQuery' => $keyword,
        ]);
    }
}

