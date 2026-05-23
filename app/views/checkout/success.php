<div class="row justify-content-center">
    <div class="col-md-7">
        <h1 class="h3 mb-3">Dat hang thanh cong</h1>

        <?php if (!empty($order)): ?>
            <div class="alert alert-success">
                Ma don: <strong>#<?= (int) $order['id'] ?></strong><br>
                Trang thai: <strong><?= htmlspecialchars((string) $order['status']) ?></strong><br>
                Phuong thuc: <strong><?= htmlspecialchars((string) $payment_method) ?></strong>
            </div>

            <h2 class="h5">Dia chi giao hang</h2>
            <div class="border rounded p-3 bg-white">
                <?= nl2br(htmlspecialchars((string) $order['shipping_address'])) ?>
            </div>
        <?php else: ?>
            <div class="alert alert-success">
                Ma don: <strong>#<?= (int) $order_id ?></strong><br>
                Phuong thuc: <strong><?= htmlspecialchars((string) $payment_method) ?></strong>
            </div>
        <?php endif; ?>

        <a class="btn btn-primary" href="<?= app_url('/') ?>">Tiep tuc mua hang</a>
        <a class="btn btn-outline-secondary" href="<?= app_url('/cart') ?>">Xem gio hang</a>
    </div>
</div>

