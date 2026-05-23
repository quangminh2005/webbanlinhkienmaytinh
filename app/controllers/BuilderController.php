<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Product;

class BuilderController extends Controller
{
    public function index(): void
    {
        $productModel = new Product();
        $cpus = $productModel->byCategorySlug('cpu');
        $mainboards = $productModel->byCategorySlug('mainboard');
        $vgaCards = $productModel->byCategorySlug('vga');
        $psuList = $productModel->byCategorySlug('psu');

        $selectedCpu = isset($_GET['cpu']) ? $productModel->find((int) $_GET['cpu']) : null;
        $selectedMain = isset($_GET['main']) ? $productModel->find((int) $_GET['main']) : null;
        $selectedVga = isset($_GET['vga']) ? $productModel->find((int) $_GET['vga']) : null;
        $selectedPsu = isset($_GET['psu']) ? $productModel->find((int) $_GET['psu']) : null;

        $warnings = [];

        if ($selectedCpu && $selectedMain && $selectedCpu['socket'] !== $selectedMain['socket']) {
            $warnings[] = 'CPU va Mainboard khong cung socket.';
        }

        if ($selectedVga && $selectedPsu) {
            $required = ((int) $selectedVga['wattage']) + 250;
            if ((int) $selectedPsu['wattage'] < $required) {
                $warnings[] = 'Nguon co the khong du cong suat cho VGA da chon.';
            }
        }

        $this->view('builder/index', [
            'cpus' => $cpus,
            'mainboards' => $mainboards,
            'vgaCards' => $vgaCards,
            'psuList' => $psuList,
            'selectedCpu' => $selectedCpu,
            'selectedMain' => $selectedMain,
            'selectedVga' => $selectedVga,
            'selectedPsu' => $selectedPsu,
            'warnings' => $warnings,
        ]);
    }
}

