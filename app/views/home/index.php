<?php

$heroSlides = hero_slider_image_urls();

?>

<div id="homeHeroCarousel" class="carousel slide home-hero-carousel mb-4 rounded-3 overflow-hidden shadow-sm border" data-bs-ride="carousel" data-bs-interval="5000">

    <?php if ($heroSlides !== []): ?>

        <div class="carousel-indicators">
            <?php foreach ($heroSlides as $i => $_url): ?>
                <button type="button" data-bs-target="#homeHeroCarousel" data-bs-slide-to="<?= (int) $i ?>" <?= $i === 0 ? 'class="active" aria-current="true"' : '' ?> aria-label="Slide <?= (int) $i + 1 ?>"></button>
            <?php endforeach; ?>
        </div>

        <div class="carousel-inner">
            <?php foreach ($heroSlides as $i => $slideUrl): ?>
                <div class="carousel-item<?= $i === 0 ? ' active' : '' ?>">
                    <img src="<?= htmlspecialchars($slideUrl) ?>" class="d-block w-100" alt="Su kien noi bat <?= (int) $i + 1 ?>" loading="<?= $i === 0 ? 'eager' : 'lazy' ?>">
                </div>
            <?php endforeach; ?>
        </div>

    <?php else: ?>

        <div class="carousel-indicators">
            <button type="button" data-bs-target="#homeHeroCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
            <button type="button" data-bs-target="#homeHeroCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
            <button type="button" data-bs-target="#homeHeroCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
        </div>

        <div class="carousel-inner">
            <div class="carousel-item active">
                <div class="hero-slide-fallback hero-slide-1">
                    <div>
                        <p class="small text-white-50 mb-1 text-uppercase">Noi bat</p>
                        <h2 class="h4 mb-0">Linh kien chinh hang, gia tot</h2>
                    </div>
                </div>
            </div>
            <div class="carousel-item">
                <div class="hero-slide-fallback hero-slide-2">
                    <div>
                        <p class="small text-white-50 mb-1 text-uppercase">Cong cu</p>
                        <h2 class="h4 mb-0">Tu rap may voi Build PC</h2>
                    </div>
                </div>
            </div>
            <div class="carousel-item">
                <div class="hero-slide-fallback hero-slide-3">
                    <div>
                        <p class="small text-white-50 mb-1 text-uppercase">Uu dai</p>
                        <h2 class="h4 mb-0">Giao hang nhanh, ho tro tan tinh</h2>
                    </div>
                </div>
            </div>
        </div>

    <?php endif; ?>

    <button class="carousel-control-prev" type="button" data-bs-target="#homeHeroCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Truoc</span>
    </button>

    <button class="carousel-control-next" type="button" data-bs-target="#homeHeroCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Sau</span>
    </button>

</div>

<h1 class="h3 mb-3">Danh sách sản phẩm</h1>

<form class="row g-2 mb-4 align-items-end" method="get" action="<?= app_url('/') ?>">
    <div class="col-md-4">
        <label class="form-label small text-muted mb-1">Tu khoa</label>
        <input
            type="search"
            name="q"
            class="form-control"
            placeholder="Tim theo ten hoac mo ta..."
            value="<?= htmlspecialchars((string) ($searchQuery ?? '')) ?>"
        >
    </div>
    <div class="col-md-4">
        <label class="form-label small text-muted mb-1">Danh muc</label>
        <select name="category" class="form-select">
            <option value="">Tat ca danh muc</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= $category['id'] ?>" <?= ($selectedCategory === (int) $category['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($category['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <button class="btn btn-primary w-100" type="submit">Loc</button>
    </div>
</form>

<?php if (empty($products)): ?>
    <p class="text-muted">Khong tim thay san pham phu hop. Thu doi tu khoa hoac danh muc.</p>
<?php endif; ?>

<div class="row g-3">
    <?php foreach ($products as $product): ?>
        <div class="col-md-4">
            <div class="card product-card h-100">
                <?php if (!empty($product['image_url'])): ?>
                    <img
                        src="<?= htmlspecialchars(product_image_url((string) $product['image_url'])) ?>"
                        alt="<?= htmlspecialchars($product['name']) ?>"
                        class="card-img-top"
                        style="height: 190px; object-fit: cover;"
                    >
                <?php endif; ?>
                <div class="card-body">
                    <h2 class="h6"><?= htmlspecialchars($product['name']) ?></h2>
                    <div class="text-muted small mb-2"><?= htmlspecialchars($product['category_name']) ?></div>
                    <div class="fw-bold text-danger mb-2"><?= number_format((float) $product['price']) ?> VND</div>
                    <p class="small mb-3"><?= htmlspecialchars(mb_strimwidth($product['description'], 0, 120, '...')) ?></p>
                    <a class="btn btn-outline-secondary btn-sm" href="<?= app_url('/product') ?>?id=<?= $product['id'] ?>">Chi tiet</a>
                    <form class="d-inline" method="post" action="<?= app_url('/cart/add') ?>">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <button class="btn btn-primary btn-sm">Them gio</button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
