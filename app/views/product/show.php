<div class="row">
    <?php $inStock = (int) ($product['stock_quantity'] ?? 0) > 0; ?>
    <div class="col-md-4">
        <?php if (!empty($product['image_url'])): ?>
            <img
                src="<?= htmlspecialchars(product_image_url((string) $product['image_url'])) ?>"
                alt="<?= htmlspecialchars($product['name']) ?>"
                class="img-fluid rounded border"
            >
        <?php else: ?>
            <div class="border rounded p-5 text-center text-muted">Chua co anh san pham</div>
        <?php endif; ?>
    </div>
    <div class="col-md-8">
        <h1 class="h3"><?= htmlspecialchars($product['name']) ?></h1>
        <?php if (!empty($flashSale)): ?>
            <div class="mb-3">
                <span class="badge text-bg-danger mb-2">
                    Flash Sale
                    <?php if ($flashSale['discount_type'] === 'percent'): ?>
                        -<?= number_format((float) $flashSale['discount_value']) ?>%
                    <?php else: ?>
                        -<?= number_format((float) $flashSale['discount_value']) ?> VND
                    <?php endif; ?>
                </span>
                <div class="text-muted small">
                    Gia goc: <span class="text-decoration-line-through"><?= number_format((float) $flashSale['original_price']) ?> VND</span>
                </div>
                <div class="fs-4 fw-bold text-danger">
                    Gia Flash Sale: <?= number_format((float) $flashSale['sale_price']) ?> VND
                </div>
                <?php if (!empty($flashSale['ends_at'])): ?>
                    <div class="small text-muted">Ket thuc: <?= htmlspecialchars((string) $flashSale['ends_at']) ?></div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="text-muted mb-3">Gia: <strong class="text-danger"><?= number_format((float) $product['price']) ?> VND</strong></div>
        <?php endif; ?>
        <div class="mb-3">
            <?php if ($inStock): ?>
                <span class="badge text-bg-success">Con hang</span>
            <?php else: ?>
                <span class="badge text-bg-danger">Het hang</span>
            <?php endif; ?>
        </div>
        <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>

        <ul class="list-group mb-3">
            <li class="list-group-item">Socket: <?= htmlspecialchars($product['socket'] ?: 'N/A') ?></li>
            <li class="list-group-item">RAM type: <?= htmlspecialchars($product['ram_type'] ?: 'N/A') ?></li>
            <li class="list-group-item">VRAM: <?= (int) $product['vram_gb'] ?> GB</li>
            <li class="list-group-item">Wattage: <?= (int) $product['wattage'] ?>W</li>
        </ul>

        <div class="d-flex flex-wrap gap-2">
            <form method="post" action="<?= app_url('/cart/add') ?>">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                <button class="btn btn-primary" <?= $inStock ? '' : 'disabled' ?>>Them vao gio hang</button>
            </form>
            <button
                type="button"
                class="btn btn-outline-primary"
                data-pc-chat-product-question
                data-product-name="<?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?>"
            >
                Hoi AI ve san pham nay
            </button>
        </div>
    </div>
</div>

