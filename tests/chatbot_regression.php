<?php

declare(strict_types=1);

use App\Controllers\ChatController;
use App\Core\Database;

date_default_timezone_set('Asia/Saigon');

if (!defined('BASE_PATH')) {
    define('BASE_PATH', '');
}

$_SESSION = [];

require dirname(__DIR__) . '/app/core/helpers.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = dirname(__DIR__) . '/app/' . str_replace('\\', '/', $relativeClass) . '.php';
    $file = str_replace('/Controllers/', '/controllers/', $file);
    $file = str_replace('/Models/', '/models/', $file);
    $file = str_replace('/Core/', '/core/', $file);
    if (is_file($file)) {
        require $file;
    }
});

/** @return mixed */
function invokePrivate(object $object, string $method, mixed ...$arguments): mixed
{
    $reflection = new ReflectionMethod($object, $method);
    $reflection->setAccessible(true);
    return $reflection->invoke($object, ...$arguments);
}

function setPrivate(object $object, string $property, mixed $value): void
{
    $reflection = new ReflectionProperty($object, $property);
    $reflection->setAccessible(true);
    $reflection->setValue($object, $value);
}

/** @return array<string, mixed>|null */
function productByName(string $needle): ?array
{
    $stmt = Database::connection()->prepare('
        SELECT p.id, p.name, p.price, p.stock_quantity, p.description, p.image_url,
               p.socket, p.ram_type, p.vram_gb, p.wattage,
               c.name AS category_name, c.slug AS category_slug
        FROM products p
        JOIN categories c ON c.id = p.category_id
        WHERE p.name LIKE :needle
        ORDER BY p.id ASC
        LIMIT 1
    ');
    $stmt->execute(['needle' => '%' . $needle . '%']);
    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }

    return [
        'id' => (int) $row['id'],
        'name' => (string) $row['name'],
        'category' => (string) $row['category_name'],
        'category_slug' => (string) $row['category_slug'],
        'price' => (float) $row['price'],
        'price_text' => number_format((float) $row['price']) . ' VND',
        'stock_quantity' => (int) $row['stock_quantity'],
        'in_stock' => (int) $row['stock_quantity'] > 0,
        'description' => (string) ($row['description'] ?? ''),
        'specs' => [
            'socket' => (string) ($row['socket'] ?? ''),
            'ram_type' => (string) ($row['ram_type'] ?? ''),
            'vram_gb' => (int) ($row['vram_gb'] ?? 0),
            'wattage' => (int) ($row['wattage'] ?? 0),
        ],
        'url' => '/product?id=' . (int) $row['id'],
    ];
}

/** @param array<string, array<string, mixed>> $items */
function buildTotal(array $items): float
{
    return array_sum(array_map(
        static fn (array $product): float => (float) ($product['price'] ?? 0),
        $items
    ));
}

/** @param array<string, array<string, mixed>> $items */
function completeBuild(array $items): bool
{
    foreach (['cpu', 'mainboard', 'ram', 'vga', 'psu', 'case', 'ssd', 'cooler'] as $slug) {
        if (empty($items[$slug])) {
            return false;
        }
    }
    return true;
}

$controller = new ChatController();
$tests = [];

$run = static function (string $name, callable $assertion) use (&$tests, $controller): void {
    $_SESSION['chat_build_preferences'] = [];
    $_SESSION['chat_product_context'] = [];
    setPrivate($controller, 'activeChatSessionId', 'regression-' . count($tests));

    try {
        $result = $assertion($controller);
        $tests[] = ['name' => $name, 'passed' => $result === true, 'detail' => $result === true ? '' : (string) $result];
    } catch (Throwable $exception) {
        $tests[] = ['name' => $name, 'passed' => false, 'detail' => $exception->getMessage()];
    }
};

$run('01 Không bịa Ryzen 5 7500F', static function (ChatController $controller): bool|string {
    return invokePrivate($controller, 'productMentionedInText', 'cpu', 'Ryzen 5 7500F còn hàng không?') === null
        ?: 'Đã nhận nhầm một CPU khác thành Ryzen 5 7500F.';
});

$run('02 Tìm đúng RTX 4060', static function (ChatController $controller): bool|string {
    $product = invokePrivate($controller, 'productMentionedInText', 'vga', 'Shop có RTX 4060 nào?');
    return is_array($product) && str_contains(strtoupper((string) $product['name']), '4060')
        ?: 'Không tìm thấy VGA RTX 4060 hiện có.';
});

$run('03 Liệt kê mainboard B650M', static function (ChatController $controller): bool|string {
    $products = invokePrivate($controller, 'productsByCategoryForAi', 'mainboard', 0);
    $matches = array_filter($products, static fn (array $p): bool => str_contains(strtoupper((string) $p['name']), 'B650M'));
    return count($matches) >= 1 ?: 'Không tìm thấy mainboard B650M.';
});

$run('04 Trang chi tiết trả đúng sản phẩm', static function (ChatController $controller): bool|string {
    $known = productByName('Ryzen 7 7700');
    if ($known === null) {
        return 'Dữ liệu kiểm thử Ryzen 7 7700 không tồn tại.';
    }
    $current = invokePrivate($controller, 'currentProductForAi', '/product?id=' . $known['id']);
    return is_array($current) && (int) $current['id'] === (int) $known['id']
        ?: 'Không đọc đúng currentProduct từ URL.';
});

$run('05 Ryzen 7 7700 chỉ ghép main AM5', static function (ChatController $controller): bool|string {
    $reply = invokePrivate($controller, 'validatedMainboardsForCpuReply', 'Ryzen 7 7700 main nào tương thích?');
    if (!is_string($reply) || !str_contains(strtoupper($reply), 'AM5')) {
        return 'Không trả được mainboard AM5.';
    }
    return !str_contains(strtoupper($reply), 'LGA1700') && !str_contains(strtoupper($reply), 'LGA1851')
        ?: 'Có mainboard sai socket trong kết quả.';
});

$buildTest = static function (string $message) use ($controller): array {
    invokePrivate($controller, 'rememberBuildPreferences', $message);
    $budget = invokePrivate($controller, 'budgetFromMessage', $message);
    return invokePrivate($controller, 'fallbackBuildItems', $budget, $message);
};

$run('06 Gaming PC 20 triệu không xuất cấu hình lỗi', static function (ChatController $controller) use ($buildTest): bool|string {
    $items = $buildTest('Build PC gaming 20 triệu');
    $errors = invokePrivate($controller, 'validateBuildCompatibility', $items, true);
    return completeBuild($items) && $errors === [] ?: 'Cấu hình thiếu nhóm hoặc không tương thích: ' . implode('; ', $errors);
});

$run('07 PC AI 30 triệu tương thích', static function (ChatController $controller) use ($buildTest): bool|string {
    $items = $buildTest('Build PC AI 30 triệu dùng NVIDIA');
    $errors = invokePrivate($controller, 'validateBuildCompatibility', $items, true);
    return completeBuild($items) && $errors === [] && str_contains(strtoupper((string) $items['vga']['name']), 'RTX')
        ?: 'PC AI thiếu nhóm, sai tương thích hoặc không ưu tiên NVIDIA.';
});

$run('08 PC lập trình 15 triệu tương thích', static function (ChatController $controller) use ($buildTest): bool|string {
    $items = $buildTest('Build PC lập trình 15 triệu');
    $errors = invokePrivate($controller, 'validateBuildCompatibility', $items, true);
    return completeBuild($items) && $errors === [] ?: 'Cấu hình lập trình thiếu nhóm hoặc không tương thích.';
});

$run('09 Gaming PC 80 triệu trong sai số 5%', static function (ChatController $controller) use ($buildTest): bool|string {
    $items = $buildTest('Build PC gaming 80 triệu');
    $errors = invokePrivate($controller, 'validateBuildCompatibility', $items, true);
    $total = buildTotal($items);
    return completeBuild($items) && $errors === [] && $total <= 84000000
        ?: 'Tổng ' . number_format($total)
            . '; nhóm: ' . implode(',', array_keys($items))
            . '; lỗi: ' . implode('; ', $errors);
});

$run('10 Phát hiện Ryzen và B760M sai socket', static function (ChatController $controller): bool|string {
    $cpu = productByName('Ryzen 7 7700');
    $main = productByName('B760M');
    if ($cpu === null || $main === null) {
        return 'Thiếu dữ liệu Ryzen/B760M để kiểm thử.';
    }
    $errors = invokePrivate($controller, 'validateBuildCompatibility', ['cpu' => $cpu, 'mainboard' => $main]);
    return in_array('CPU va Mainboard khong cung socket.', $errors, true) ?: 'Không phát hiện sai socket.';
});

$run('11 Không bịa i5-14400F', static function (ChatController $controller): bool|string {
    return invokePrivate($controller, 'productMentionedInText', 'cpu', 'i5-14400F dùng main B650M được không?') === null
        ?: 'Đã nhận nhầm CPU khác thành i5-14400F.';
});

$run('12 Phát hiện DDR4 và main DDR5', static function (ChatController $controller): bool|string {
    $ram = productByName('DDR4 3200');
    $main = productByName('B650M');
    if ($ram === null || $main === null) {
        return 'Thiếu dữ liệu RAM DDR4/B650M.';
    }
    $errors = invokePrivate($controller, 'validateBuildCompatibility', ['mainboard' => $main, 'ram' => $ram]);
    return in_array('Mainboard va RAM khong cung chuan RAM.', $errors, true) ?: 'Không phát hiện sai chuẩn RAM.';
});

$run('13 Ghi nhớ đổi CPU sang AMD', static function (ChatController $controller): bool|string {
    invokePrivate($controller, 'rememberBuildPreferences', 'Build PC gaming 25 triệu');
    invokePrivate($controller, 'rememberBuildPreferences', 'đổi sang AMD và build lại');
    $items = invokePrivate($controller, 'fallbackBuildItems', 25000000.0, 'đổi sang AMD và build lại');
    return isset($items['cpu']) && str_contains(strtoupper((string) $items['cpu']['name']), 'RYZEN')
        ?: 'Không ghi nhớ yêu cầu chuyển CPU AMD.';
});

$run('14 Ghi nhớ case trắng', static function (ChatController $controller): bool|string {
    invokePrivate($controller, 'rememberBuildPreferences', 'Build PC gaming 25 triệu');
    invokePrivate($controller, 'rememberBuildPreferences', 'tôi muốn case màu trắng');
    $preferences = invokePrivate($controller, 'buildPreferences');
    return ($preferences['case_color'] ?? '') === 'white' ?: 'Không ghi nhớ yêu cầu case trắng.';
});

$run('15 Không bịa RTX 6090', static function (ChatController $controller): bool|string {
    return invokePrivate($controller, 'productMentionedInText', 'vga', 'RTX 6090 còn hàng không?') === null
        ?: 'Đã nhận nhầm VGA khác thành RTX 6090.';
});

$run('16 Không bịa Ryzen 9999X', static function (ChatController $controller): bool|string {
    return invokePrivate($controller, 'productMentionedInText', 'cpu', 'Ryzen 9999X giá bao nhiêu?') === null
        ?: 'Đã nhận nhầm CPU khác thành Ryzen 9999X.';
});

$run('17 Có CPU AMD gaming gần 4 triệu', static function (ChatController $controller): bool|string {
    $products = invokePrivate($controller, 'productsByCategoryForAi', 'cpu', 0);
    $matches = array_filter($products, static fn (array $p): bool => str_contains(strtoupper((string) $p['name']), 'RYZEN'));
    return $matches !== [] ?: 'Không tìm thấy CPU AMD.';
});

$run('18 Có nguồn 650W Bronze', static function (ChatController $controller): bool|string {
    $products = invokePrivate($controller, 'productsByCategoryForAi', 'psu', 0);
    foreach ($products as $product) {
        if ((int) ($product['specs']['wattage'] ?? 0) === 650 && str_contains(strtoupper((string) $product['name']), 'BRONZE')) {
            return true;
        }
    }
    return 'Không tìm thấy PSU 650W Bronze.';
});

$run('19 Có SSD NVMe 1TB', static function (ChatController $controller): bool|string {
    $products = invokePrivate($controller, 'productsByCategoryForAi', 'ssd', 0);
    foreach ($products as $product) {
        $name = strtoupper((string) $product['name']);
        if (str_contains($name, '1TB') && str_contains($name, 'NVME')) {
            return true;
        }
    }
    return 'Không tìm thấy SSD NVMe 1TB.';
});

$run('20 Main AM5 DDR5 chỉ trả đúng chuẩn', static function (ChatController $controller): bool|string {
    $products = invokePrivate($controller, 'productsByCategoryForAi', 'mainboard', 0);
    $matches = array_filter($products, static function (array $product): bool {
        return strtoupper((string) ($product['specs']['socket'] ?? '')) === 'AM5'
            && strtoupper((string) ($product['specs']['ram_type'] ?? '')) === 'DDR5';
    });
    return $matches !== [] ?: 'Không tìm thấy mainboard AM5 DDR5.';
});

$run('21 Tổng hợp AMD, NVIDIA, WiFi, SSD 1TB', static function (ChatController $controller) use ($buildTest): bool|string {
    $items = $buildTest('Build PC gaming 25 triệu dùng AMD, VGA NVIDIA, main có WiFi, SSD NVMe 1TB, case trắng');
    $errors = invokePrivate($controller, 'validateBuildCompatibility', $items, true);
    $cpuOk = isset($items['cpu']) && str_contains(strtoupper((string) $items['cpu']['name']), 'RYZEN');
    $gpuOk = isset($items['vga']) && str_contains(strtoupper((string) $items['vga']['name']), 'RTX');
    $wifiOk = isset($items['mainboard']) && str_contains(strtoupper((string) $items['mainboard']['name']), 'WIFI');
    $ssdOk = isset($items['ssd']) && str_contains(strtoupper((string) $items['ssd']['name']), '1TB');
    return completeBuild($items) && $errors === [] && $cpuOk && $gpuOk && $wifiOk && $ssdOk
        ?: 'Không đáp ứng đầy đủ AMD/NVIDIA/WiFi/SSD 1TB hoặc cấu hình không tương thích.';
});

$run('22 SSD NVMe 1TB la truy van san pham doc lap', static function (ChatController $controller): bool|string {
    invokePrivate($controller, 'rememberBuildPreferences', 'Build PC gaming 25 trieu');

    if (invokePrivate($controller, 'shouldUseValidatedBuildReply', 'SSD NVMe 1TB')) {
        return 'Truy van SSD doc lap bi chuyen nham sang nhanh Build PC.';
    }

    $slugs = invokePrivate($controller, 'categorySlugsFromQuery', 'SSD NVMe 1TB');
    return in_array('ssd', $slugs, true) ?: 'Truy van khong duoc nhan dien la danh muc SSD.';
});

$run('23 Doi SSD trong cau hinh van la tiep tuc Build PC', static function (ChatController $controller): bool|string {
    invokePrivate($controller, 'rememberBuildPreferences', 'Build PC gaming 25 trieu');

    return invokePrivate($controller, 'shouldUseValidatedBuildReply', 'doi SSD sang 1TB')
        ?: 'Yeu cau doi SSD trong cau hinh khong duoc nhan dien la tiep tuc Build PC.';
});

$run('24 Build PC hoi muc dich truoc ngan sach', static function (ChatController $controller): bool|string {
    invokePrivate($controller, 'rememberBuildPreferences', 'Build PC');
    $reply = invokePrivate($controller, 'fallbackBuildReply', 'Build PC');

    return str_contains(invokePrivate($controller, 'foldVietnamese', $reply), 'muc dich')
        && !str_contains(invokePrivate($controller, 'foldVietnamese', $reply), 'goi y cau hinh')
        ?: 'Khong hoi muc dich truoc khi dung cau hinh.';
});

$run('25 Co muc dich thi hoi ngan sach', static function (ChatController $controller): bool|string {
    invokePrivate($controller, 'rememberBuildPreferences', 'Build PC');
    invokePrivate($controller, 'rememberBuildPreferences', 'choi game');
    $reply = invokePrivate($controller, 'fallbackBuildReply', 'choi game');

    return invokePrivate($controller, 'shouldUseValidatedBuildReply', 'choi game')
        && str_contains(invokePrivate($controller, 'foldVietnamese', $reply), 'ngan sach')
        && !str_contains(invokePrivate($controller, 'foldVietnamese', $reply), 'goi y cau hinh')
        ?: 'Khong hoi ngan sach sau khi da co muc dich.';
});

$run('26 Du muc dich va ngan sach moi dung cau hinh', static function (ChatController $controller): bool|string {
    invokePrivate($controller, 'rememberBuildPreferences', 'Build PC');
    invokePrivate($controller, 'rememberBuildPreferences', 'choi game');
    invokePrivate($controller, 'rememberBuildPreferences', '20 trieu');
    $reply = invokePrivate($controller, 'fallbackBuildReply', '20 trieu');

    return invokePrivate($controller, 'shouldUseValidatedBuildReply', '20 trieu')
        && str_contains(invokePrivate($controller, 'foldVietnamese', $reply), 'goi y cau hinh pc')
        ?: 'Khong dung cau hinh sau khi da du muc dich va ngan sach.';
});

$run('27 Khong dung cau hinh neu du lieu vuot xa ngan sach', static function (ChatController $controller): bool|string {
    invokePrivate($controller, 'rememberBuildPreferences', 'Build PC gaming 5 trieu');
    $reply = invokePrivate($controller, 'fallbackBuildReply', 'Build PC gaming 5 trieu');
    $foldedReply = invokePrivate($controller, 'foldVietnamese', $reply);

    return str_contains($foldedReply, 'khong co cau hinh pc hoan chinh phu hop ngan sach')
        && str_contains($foldedReply, 'khong tu dung cau hinh vuot ngan sach')
        && !str_contains($foldedReply, '- cpu:')
        ?: 'Van liet ke cau hinh khi du lieu shop khong dap ung ngan sach.';
});

$passed = count(array_filter($tests, static fn (array $test): bool => $test['passed']));
$total = count($tests);
$rate = $total > 0 ? ($passed / $total) * 100 : 0;

foreach ($tests as $index => $test) {
    echo sprintf(
        "%02d. [%s] %s%s\n",
        $index + 1,
        $test['passed'] ? 'PASS' : 'FAIL',
        $test['name'],
        $test['detail'] !== '' ? ' - ' . $test['detail'] : ''
    );
}

echo "\nKết quả: {$passed}/{$total} - " . number_format($rate, 2) . "%\n";
exit($rate >= 90 ? 0 : 1);
