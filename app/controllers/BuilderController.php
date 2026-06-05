<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Product;

class BuilderController extends Controller
{
    private array $parts = [
        'cpu' => ['label' => 'CPU', 'slug' => 'cpu'],
        'main' => ['label' => 'Mainboard', 'slug' => 'mainboard'],
        'ram' => ['label' => 'RAM', 'slug' => 'ram'],
        'vga' => ['label' => 'VGA', 'slug' => 'vga'],
        'psu' => ['label' => 'Nguon', 'slug' => 'psu'],
        'case' => ['label' => 'Vo case', 'slug' => 'case'],
        'ssd' => ['label' => 'SSD', 'slug' => 'ssd'],
        'cooler' => ['label' => 'Tan nhiet', 'slug' => 'cooler'],
        'hdd' => ['label' => 'HDD (tuy chon)', 'slug' => 'hdd'],
    ];

    public function index(): void
    {
        $productModel = new Product();
        $options = [];
        $selected = [];

        foreach ($this->parts as $key => $part) {
            $options[$key] = $productModel->byCategorySlug($part['slug']);
            $selected[$key] = isset($_GET[$key]) ? $productModel->find((int) $_GET[$key]) : null;
        }

        $warnings = [];

        if ($selected['cpu'] && $selected['main'] && $selected['cpu']['socket'] !== $selected['main']['socket']) {
            $warnings[] = 'CPU va Mainboard khong cung socket.';
        }

        if ($selected['main'] && $selected['ram']) {
            $mainRamType = trim((string) ($selected['main']['ram_type'] ?? ''));
            $ramType = trim((string) ($selected['ram']['ram_type'] ?? ''));
            if ($mainRamType !== '' && $ramType !== '' && $mainRamType !== $ramType) {
                $warnings[] = 'RAM va Mainboard khong cung loai RAM.';
            }
        }

        if ($selected['vga'] && $selected['psu']) {
            $required = ((int) $selected['vga']['wattage']) + 250;
            if ((int) $selected['psu']['wattage'] < $required) {
                $warnings[] = 'Nguon co the khong du cong suat cho VGA da chon.';
            }
        }

        $total = 0.0;
        $selectedItems = [];
        $outOfStock = [];
        foreach ($selected as $key => $product) {
            if (!$product) {
                continue;
            }

            $total += (float) $product['price'];
            $selectedItems[$key] = $product;
            if ((int) $product['stock_quantity'] <= 0) {
                $outOfStock[] = $product;
            }
        }

        $this->view('builder/index', [
            'parts' => $this->parts,
            'options' => $options,
            'selected' => $selected,
            'selectedItems' => $selectedItems,
            'total' => $total,
            'outOfStock' => $outOfStock,
            'warnings' => $warnings,
        ]);
    }

    public function addToCart(): void
    {
        $productModel = new Product();
        $added = 0;
        $skipped = [];

        foreach ($this->parts as $key => $part) {
            $productId = (int) ($_POST[$key] ?? 0);
            if ($productId <= 0) {
                continue;
            }

            $product = $productModel->find($productId);
            if (!$product) {
                continue;
            }

            $currentQty = (int) ($_SESSION['cart'][$productId] ?? 0);
            if ((int) $product['stock_quantity'] <= $currentQty) {
                $skipped[] = (string) $product['name'];
                continue;
            }

            if (!isset($_SESSION['cart'][$productId])) {
                $_SESSION['cart'][$productId] = 0;
            }
            $_SESSION['cart'][$productId]++;
            $added++;
        }

        if ($added > 0) {
            $_SESSION['success'] = 'Da them ' . $added . ' linh kien con hang vao gio.';
        }

        if ($skipped !== []) {
            $_SESSION['error'] = 'Mot so linh kien het hang nen khong duoc them: ' . implode(', ', $skipped);
            $this->redirect('/build-pc?' . http_build_query($_POST));
        }

        $this->redirect('/cart');
    }
}

