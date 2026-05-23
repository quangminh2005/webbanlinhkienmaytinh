<!doctype html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PC Parts Shop</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= app_url('/css/site.css') ?>">
</head>
<body class="bg-light d-flex flex-column min-vh-100">
<?php
$bannerLogoUrl = bannerquangcao_logo_url();
$bannerLeftUrl = bannerquangcao_side_image(true);
$bannerRightUrl = bannerquangcao_side_image(false);
$cartSession = $_SESSION['cart'] ?? [];
$cartItemCount = is_array($cartSession) ? array_sum(array_map('intval', $cartSession)) : 0;
?>

<header class="site-header">
    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm site-navbar">
        <div class="container">
            <a class="navbar-brand site-brand" href="<?= app_url('/') ?>">
                <?php if ($bannerLogoUrl): ?>
                    <img src="<?= htmlspecialchars($bannerLogoUrl) ?>" alt="PC Parts Shop">
                <?php endif; ?>
                <span>PC Parts Shop</span>
            </a>

            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarMain"
                aria-controls="navbarMain"
                aria-expanded="false"
                aria-label="Toggle navigation"
            >
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse site-nav-content" id="navbarMain">
                <div class="navbar-nav site-primary-nav">
                    <a class="nav-link" href="<?= app_url('/') ?>">San pham</a>
                    <a class="nav-link site-build-link" href="<?= app_url('/build-pc') ?>">Build PC</a>
                </div>

                <form class="site-search" method="get" action="<?= app_url('/') ?>" role="search">
                    <input
                        class="form-control form-control-sm"
                        type="search"
                        name="q"
                        placeholder="Tim san pham theo ten..."
                        value="<?= htmlspecialchars((string) ($_GET['q'] ?? '')) ?>"
                        aria-label="Tim kiem san pham"
                    >
                    <button class="btn btn-sm btn-light flex-shrink-0" type="submit">Tim</button>
                </form>

                <div class="navbar-nav site-actions">
                    <a class="nav-link site-cart-link" href="<?= app_url('/cart') ?>">
                        <span>Gio hang</span>
                        <?php if ($cartItemCount > 0): ?>
                            <span class="badge rounded-pill text-bg-danger cart-nav-badge">
                                <?= $cartItemCount > 99 ? '99+' : (string) $cartItemCount ?>
                            </span>
                        <?php endif; ?>
                    </a>

                    <?php if (!empty($_SESSION['user'])): ?>
                        <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                            <div class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Admin
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="<?= app_url('/admin/products') ?>">Quan ly san pham</a></li>
                                    <li><a class="dropdown-item" href="<?= app_url('/admin/orders') ?>">Quan ly don hang</a></li>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <div class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle site-user-link" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <?= htmlspecialchars($_SESSION['user']['name']) ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?= app_url('/orders') ?>">Don hang cua toi</a></li>
                                <li><a class="dropdown-item" href="<?= app_url('/profile') ?>">Thong tin ca nhan</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?= app_url('/auth/logout') ?>">Dang xuat</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a class="nav-link" href="<?= app_url('/auth/login') ?>">Dang nhap</a>
                        <a class="nav-link" href="<?= app_url('/auth/register') ?>">Dang ky</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
</header>

<div class="container-fluid flex-grow-1 px-0">
    <div class="row g-0 layout-page-row">
        <aside class="col-lg-2 d-none d-lg-flex flex-column align-items-stretch border-end bg-white py-4 px-2">
            <div class="sticky-top pt-1" style="top: 1rem;">
                <?php if ($bannerLeftUrl): ?>
                    <a href="<?= app_url('/') ?>" class="layout-banner-link shadow-sm mb-3 text-decoration-none bg-light">
                        <img src="<?= htmlspecialchars($bannerLeftUrl) ?>" alt="Quang cao" loading="lazy">
                    </a>
                <?php else: ?>
                    <div class="rounded-3 border border-dashed bg-light p-3 small text-muted text-center mb-3">
                        Them anh vao <code class="small">public/bannerquangcao/</code>
                        (vd: <code>banner-trai.png</code> hoac <code>banner-left.jpg</code>)
                    </div>
                <?php endif; ?>
            </div>
        </aside>

        <main class="col-lg-8 col-12 py-4 px-3">
            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
