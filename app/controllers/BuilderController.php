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

        $warnings = $this->compatibilityErrors($selected);

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
        $selected = [];

        foreach ($this->parts as $key => $part) {
            $productId = (int) ($_POST[$key] ?? 0);
            if ($productId <= 0) {
                continue;
            }

            $product = $productModel->find($productId);
            if ($product) {
                $selected[$key] = $product;
            }
        }

        $compatibilityErrors = $this->compatibilityErrors($selected);
        if ($compatibilityErrors !== []) {
            $_SESSION['error'] = implode(' ', $compatibilityErrors);
            $this->redirect('/build-pc?' . http_build_query($_POST));
            return;
        }

        foreach ($this->parts as $key => $part) {
            $product = $selected[$key] ?? null;
            if (!$product) {
                continue;
            }

            $productId = (int) $product['id'];

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

    /** @param array<string, array<string, mixed>|null> $selected */
    private function compatibilityErrors(array $selected): array
    {
        $errors = [];

        if (!empty($selected['cpu']) && !empty($selected['main'])) {
            $cpuSocket = $this->normalizeCompatibilityValue((string) ($selected['cpu']['socket'] ?? ''));
            $mainSocket = $this->normalizeCompatibilityValue((string) ($selected['main']['socket'] ?? ''));
            if ($cpuSocket === '' || $mainSocket === '') {
                $errors[] = 'Khong du du lieu socket de xac minh CPU va Mainboard.';
            } elseif ($cpuSocket !== $mainSocket) {
                $errors[] = 'CPU va Mainboard khong cung socket.';
            }
        }

        if (!empty($selected['main']) && !empty($selected['ram'])) {
            $mainRamType = $this->normalizeCompatibilityValue((string) ($selected['main']['ram_type'] ?? ''));
            $ramType = $this->normalizeCompatibilityValue((string) ($selected['ram']['ram_type'] ?? ''));
            if ($mainRamType === '' || $ramType === '') {
                $errors[] = 'Khong du du lieu de xac minh loai RAM cua Mainboard va RAM.';
            } elseif ($mainRamType !== $ramType) {
                $errors[] = 'RAM va Mainboard khong cung loai RAM.';
            }
        }

        if (!empty($selected['vga']) && !empty($selected['psu'])) {
            $vgaWattage = (int) ($selected['vga']['wattage'] ?? 0);
            $psuWattage = (int) ($selected['psu']['wattage'] ?? 0);
            if ($vgaWattage <= 0 || $psuWattage <= 0) {
                $errors[] = 'Khong du du lieu cong suat de xac minh VGA va Nguon.';
            } elseif ($psuWattage < $this->minimumPsuWattage($vgaWattage)) {
                $errors[] = 'Nguon khong du cong suat du phong cho VGA da chon.';
            }
        }

        return array_values(array_unique($errors));
    }

    private function normalizeCompatibilityValue(string $value): string
    {
        return strtoupper(preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($value))) ?? '');
    }

    private function minimumPsuWattage(int $vgaWattage): int
    {
        return max(450, $vgaWattage + 250);
    }
}

