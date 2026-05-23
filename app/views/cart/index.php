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
                    <td><?= number_format((float) $item['product']['price']) ?></td>
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
    <h2 class="h5 text-end">Tong cong: <span class="text-danger"><?= number_format((float) $total) ?> VND</span></h2>
    <div class="d-flex justify-content-end">
        <a class="btn btn-success mt-3" href="<?= app_url('/checkout') ?>">Thanh toan</a>
    </div>
<?php endif; ?>

