<h1 class="h3 mb-3">Lich su don hang</h1>

<?php if (empty($orders)): ?>
    <div class="alert alert-info">Ban chua co don hang nao.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
            <tr>
                <th>Ma don</th>
                <th>Trang thai</th>
                <th>Tong tien</th>
                <th>Thoi gian</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td>#<?= (int) $order['id'] ?></td>
                    <td><?= htmlspecialchars((string) $order['status']) ?></td>
                    <td><?= number_format((float) $order['total_amount']) ?> VND</td>
                    <td><?= htmlspecialchars((string) $order['created_at']) ?></td>
                    <td>
                        <a class="btn btn-sm btn-outline-primary" href="<?= app_url('/orders/view') ?>?id=<?= (int) $order['id'] ?>">
                            Xem
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

