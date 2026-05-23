<?php

namespace App\Core;

class Controller
{
    protected function view(string $path, array $data = []): void
    {
        extract($data);
        require __DIR__ . '/../views/layouts/header.php';
        require __DIR__ . '/../views/' . $path . '.php';
        require __DIR__ . '/../views/layouts/footer.php';
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . app_url($path));
        exit;
    }
}

