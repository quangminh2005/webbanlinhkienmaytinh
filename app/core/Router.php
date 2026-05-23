<?php

namespace App\Core;

class Router
{
    public function dispatch(string $uri, string $method): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        $path = rtrim($path, '/');
        $path = $path === '' ? '/' : $path;

        if ($path === '/' && $method === 'GET') {
            (new \App\Controllers\HomeController())->index();
            return;
        }

        if ($path === '/product' && $method === 'GET') {
            (new \App\Controllers\ProductController())->show();
            return;
        }

        if ($path === '/cart' && $method === 'GET') {
            (new \App\Controllers\CartController())->index();
            return;
        }

        if ($path === '/cart/add' && $method === 'POST') {
            (new \App\Controllers\CartController())->add();
            return;
        }

        if ($path === '/cart/remove' && $method === 'POST') {
            (new \App\Controllers\CartController())->remove();
            return;
        }

        if ($path === '/checkout' && $method === 'GET') {
            (new \App\Controllers\CheckoutController())->form();
            return;
        }

        if ($path === '/checkout' && $method === 'POST') {
            (new \App\Controllers\CheckoutController())->submit();
            return;
        }

        if ($path === '/build-pc' && $method === 'GET') {
            (new \App\Controllers\BuilderController())->index();
            return;
        }

        if ($path === '/auth/login' && $method === 'GET') {
            (new \App\Controllers\AuthController())->loginForm();
            return;
        }

        if ($path === '/auth/login' && $method === 'POST') {
            (new \App\Controllers\AuthController())->login();
            return;
        }

        if ($path === '/auth/register' && $method === 'GET') {
            (new \App\Controllers\AuthController())->registerForm();
            return;
        }

        if ($path === '/auth/register' && $method === 'POST') {
            (new \App\Controllers\AuthController())->register();
            return;
        }

        if ($path === '/auth/logout' && $method === 'GET') {
            (new \App\Controllers\AuthController())->logout();
            return;
        }

        if ($path === '/profile' && $method === 'GET') {
            (new \App\Controllers\ProfileController())->show();
            return;
        }

        if ($path === '/profile' && $method === 'POST') {
            (new \App\Controllers\ProfileController())->update();
            return;
        }

        if ($path === '/admin/products' && $method === 'GET') {
            (new \App\Controllers\AdminController())->products();
            return;
        }

        if ($path === '/admin/products/create' && $method === 'POST') {
            (new \App\Controllers\AdminController())->createProduct();
            return;
        }

        if ($path === '/admin/products/import' && $method === 'POST') {
            (new \App\Controllers\AdminController())->importProducts();
            return;
        }

        if ($path === '/admin/products/update' && $method === 'POST') {
            (new \App\Controllers\AdminController())->updateProduct();
            return;
        }

        if ($path === '/admin/products/delete' && $method === 'POST') {
            (new \App\Controllers\AdminController())->deleteProduct();
            return;
        }

        if ($path === '/orders' && $method === 'GET') {
            (new \App\Controllers\OrdersController())->index();
            return;
        }

        if ($path === '/orders/view' && $method === 'GET') {
            (new \App\Controllers\OrdersController())->detail();
            return;
        }

        if ($path === '/orders/return' && $method === 'POST') {
            (new \App\Controllers\OrdersController())->returnOrder();
            return;
        }

        if ($path === '/reviews/create' && $method === 'POST') {
            (new \App\Controllers\ReviewsController())->create();
            return;
        }

        if ($path === '/admin/orders' && $method === 'GET') {
            (new \App\Controllers\AdminOrdersController())->orders();
            return;
        }

        if ($path === '/admin/orders/view' && $method === 'GET') {
            (new \App\Controllers\AdminOrdersController())->detail();
            return;
        }

        if ($path === '/admin/orders/update' && $method === 'POST') {
            (new \App\Controllers\AdminOrdersController())->updateStatus();
            return;
        }

        if ($path === '/api/chat' && $method === 'POST') {
            (new \App\Controllers\ChatController())->send();
            return;
        }

        http_response_code(404);
        echo '404 Not Found';
    }
}

