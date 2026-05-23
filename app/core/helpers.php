<?php

function app_url(string $path = '/'): string
{
    $base = defined('BASE_PATH') ? BASE_PATH : '';
    if ($path === '/') {
        return $base . '/';
    }
    return $base . $path;
}

/** CSS widget chat — nhúng inline để tránh lỗi 404 đường dẫn trên InfinityFree. */
function chat_widget_css(): string
{
    $path = public_storage_path('css/chat-widget.css');
    if (!is_readable($path)) {
        return <<<'CSS'
#pc-chat-root{position:fixed;bottom:1rem;right:1rem;z-index:10550;pointer-events:none;font-family:system-ui,sans-serif}
#pc-chat-root .pc-chat-interactive{pointer-events:auto}
#pc-chat-root svg{max-width:100%;height:auto;vertical-align:middle}
.pc-chat-launcher{display:inline-flex;align-items:center;gap:.5rem;padding:.55rem 1rem;border:none;border-radius:999px;background:linear-gradient(135deg,#6366f1,#8b5cf6,#d946ef);color:#fff;font-weight:600;cursor:pointer}
.pc-chat-launcher-icon svg,.pc-chat-avatar svg{width:1.15rem;height:1.15rem}
.pc-chat-panel:not(.is-open){display:none!important}
.pc-chat-panel.is-open{display:flex;flex-direction:column;position:absolute;bottom:4rem;right:0;width:min(400px,calc(100vw - 1.5rem));max-height:min(580px,80vh);background:#fff;border-radius:18px;box-shadow:0 16px 48px rgba(0,0,0,.2);overflow:hidden}
CSS;
    }

    $css = file_get_contents($path);
    return is_string($css) ? $css : '';
}

/** JS widget chat — nhúng inline (InfinityFree hay 404 file /js/chat-widget.js). */
function chat_widget_js(): string
{
    $path = public_storage_path('js/chat-widget.js');
    if (!is_readable($path)) {
        return <<<'JS'
(function(){'use strict';
var r=document.getElementById('pc-chat-root');if(!r)return;
var L=r.querySelector('.pc-chat-launcher'),P=r.querySelector('.pc-chat-panel'),C=r.querySelector('.pc-chat-close');
var o=false;function set(v){o=v;P.classList.toggle('is-open',v);if(v)P.removeAttribute('hidden');else P.setAttribute('hidden','');L.setAttribute('aria-expanded',v?'true':'false');}
if(L)L.addEventListener('click',function(){set(!o);});
if(C)C.addEventListener('click',function(){set(false);});
})();
JS;
    }

    $js = file_get_contents($path);
    return is_string($js) ? $js : '';
}

function product_image_url(string $imageUrl): string
{
    $imageUrl = trim($imageUrl);
    if ($imageUrl === '') {
        return '';
    }
    if (str_starts_with($imageUrl, 'http://') || str_starts_with($imageUrl, 'https://')) {
        return $imageUrl;
    }
    return app_url($imageUrl);
}

function public_storage_path(string $relative = ''): string
{
    $base = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public';
    if ($relative === '') {
        return $base;
    }

    return $base . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($relative, '/\\'));
}

function bannerquangcao_dir(): string
{
    return public_storage_path('bannerquangcao');
}

function bannerquangcao_asset_url(string $filename): string
{
    return app_url('/bannerquangcao/' . rawurlencode($filename));
}

/** @return string[] Danh sach file anh trong thu muc (khong tinh logo). */
function bannerquangcao_list_images(): array
{
    $dir = bannerquangcao_dir();
    if (!is_dir($dir)) {
        return [];
    }

    $allowed = ['svg', 'png', 'jpg', 'jpeg', 'webp', 'gif'];
    $files = [];
    foreach (scandir($dir) ?: [] as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        $full = $dir . DIRECTORY_SEPARATOR . $file;
        if (!is_file($full)) {
            continue;
        }
        if (preg_match('/^logo\./i', $file)) {
            continue;
        }
        $ext = strtolower((string) pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
            continue;
        }
        $files[] = $file;
    }
    sort($files);

    return $files;
}

function bannerquangcao_logo_url(): ?string
{
    $dir = bannerquangcao_dir();
    if (!is_dir($dir)) {
        return null;
    }

    foreach (['logo.svg', 'logo.png', 'logo.jpg', 'logo.jpeg', 'logo.webp'] as $name) {
        $full = $dir . DIRECTORY_SEPARATOR . $name;
        if (is_file($full)) {
            return bannerquangcao_asset_url($name);
        }
    }

    return null;
}

/**
 * Anh banner ben trai / phai: uu tien ten file banner-trai.*, banner-phai.* (hoac banner-left/right).
 * Neu khong co: lay anh dau tien / anh thu hai trong thu muc; neu chi co 1 anh thi ben phai lap lai anh do.
 */
function bannerquangcao_side_image(bool $left): ?string
{
    $dir = bannerquangcao_dir();
    if (!is_dir($dir)) {
        return null;
    }

    $patterns = $left
        ? ['banner-trai', 'banner-left', 'quangcao-trai', 'ad-left']
        : ['banner-phai', 'banner-right', 'quangcao-phai', 'ad-right'];
    $ext = ['svg', 'png', 'jpg', 'jpeg', 'webp', 'gif'];

    foreach ($patterns as $base) {
        foreach ($ext as $e) {
            $name = $base . '.' . $e;
            if (is_file($dir . DIRECTORY_SEPARATOR . $name)) {
                return bannerquangcao_asset_url($name);
            }
        }
    }

    $images = bannerquangcao_list_images();
    if ($images === []) {
        return null;
    }

    if ($left) {
        return bannerquangcao_asset_url($images[0]);
    }

    if (isset($images[1])) {
        return bannerquangcao_asset_url($images[1]);
    }

    return bannerquangcao_asset_url($images[0]);
}

function hero_slider_dir(): string
{
    return public_storage_path('hero-slider');
}

/**
 * @return string[] URL anh slider trang chu (sap xep theo ten file). Dat anh vao public/hero-slider/
 */
function hero_slider_image_urls(): array
{
    $dir = hero_slider_dir();
    if (!is_dir($dir)) {
        return [];
    }

    $allowed = ['svg', 'png', 'jpg', 'jpeg', 'webp', 'gif'];
    $files = [];
    foreach (scandir($dir) ?: [] as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        $full = $dir . DIRECTORY_SEPARATOR . $file;
        if (!is_file($full)) {
            continue;
        }
        $ext = strtolower((string) pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
            continue;
        }
        $files[] = $file;
    }
    sort($files);

    return array_map(
        static fn (string $name): string => app_url('/hero-slider/' . rawurlencode($name)),
        $files
    );
}

