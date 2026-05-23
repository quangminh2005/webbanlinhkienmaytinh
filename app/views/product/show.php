<div class="row">
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
        <div class="text-muted mb-3">Gia: <strong class="text-danger"><?= number_format((float) $product['price']) ?> VND</strong></div>
        <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>

        <ul class="list-group mb-3">
            <li class="list-group-item">Socket: <?= htmlspecialchars($product['socket'] ?: 'N/A') ?></li>
            <li class="list-group-item">RAM type: <?= htmlspecialchars($product['ram_type'] ?: 'N/A') ?></li>
            <li class="list-group-item">VRAM: <?= (int) $product['vram_gb'] ?> GB</li>
            <li class="list-group-item">Wattage: <?= (int) $product['wattage'] ?>W</li>
        </ul>

        <form method="post" action="<?= app_url('/cart/add') ?>">
            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
            <button class="btn btn-primary">Them vao gio hang</button>
        </form>
    </div>
</div>

