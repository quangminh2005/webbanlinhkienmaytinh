<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Product;

class ProductController extends Controller
{
    public function show(): void
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $product = (new Product())->find($id);

        if (!$product) {
            http_response_code(404);
            echo 'Product not found';
            return;
        }

        $this->view('product/show', ['product' => $product]);
    }
}

