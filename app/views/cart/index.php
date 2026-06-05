<h1 class="h3 mb-3">Gio hang</h1>

<?php if (empty($items)): ?>
    <div class="alert alert-info">Gio hang trong.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
            <tr>
                <th>San pham</th>
                <th>So luong</th>
                <th>Don gia</th>
                <th>Thanh tien</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['product']['name']) ?></td>
                    <td><?= (int) $item['qty'] ?></td>
                    <td>
                        <?= number_format((float) $item['unit_price']) ?>
                        <?php if ((float) $item['unit_price'] < (float) $item['original_unit_price']): ?>
                            <div class="small text-muted text-decoration-line-through"><?= number_format((float) $item['original_unit_price']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= number_format((float) $item['line_total']) ?></td>
                    <td>
                        <form method="post" action="<?= app_url('/cart/remove') ?>">
                            <input type="hidden" name="product_id" value="<?= $item['product']['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger">Xoa</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="row justify-content-end">
        <div class="col-md-5">
            <form method="post" action="<?= app_url('/cart/coupon') ?>" class="d-flex gap-2 mb-3">
                <input class="form-control" name="coupon_code" placeholder="Ma giam gia" value="<?= htmlspecialchars((string) ($pricing['coupon_code'] ?? ($_SESSION['coupon_code'] ?? ''))) ?>">
                <button class="btn btn-outline-primary">Ap dung</button>
            </form>
            <div class="text-end">
                <div>Tam tinh: <?= number_format((float) $pricing['subtotal']) ?> VND</div>
                <?php foreach ($pricing['discounts'] as $discount): ?>
                    <div class="text-success"><?= htmlspecialchars($discount['label']) ?>: -<?= number_format((float) $discount['amount']) ?> VND</div>
                <?php endforeach; ?>
                <div class="small text-muted">Hang thanh vien: <?= htmlspecialchars((string) $pricing['tier']['name']) ?></div>
                <h2 class="h5">Tong cong: <span class="text-danger"><?= number_format((float) $pricing['total']) ?> VND</span></h2>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-end">
        <a class="btn btn-success mt-3" href="<?= app_url('/checkout') ?>">Thanh toan</a>
    </div>
<?php endif; ?>

