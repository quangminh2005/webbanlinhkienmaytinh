<h1 class="h3 mb-3">Admin - Quan ly don hang</h1>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" action="<?= app_url('/admin/orders') ?>" class="row g-2">
            <div class="col-md-6">
                <select class="form-select" name="status">
                    <option value="">Tat ca trang thai</option>
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= $s ?>" <?= ((string) ($status_filter ?? '') === (string) $s) ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string) $s) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-grid">
                <button class="btn btn-primary">Loc</button>
            </div>
        </form>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-bordered table-sm bg-white">
        <thead>
        <tr>
            <th>ID</th>
            <th>User</th>
            <th>Tong tien</th>
            <th>Trang thai</th>
            <th>Thoi gian</th>
            <th>Chi tiet</th>
            <th>Cap nhat</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($orders)): ?>
            <tr>
                <td colspan="7" class="text-center text-muted">Khong co don hang.</td>
            </tr>
        <?php endif; ?>
        <?php foreach ($orders as $order): ?>
            <tr>
                <td>#<?= (int) $order['id'] ?></td>
                <td><?= (int) $order['user_id'] ?></td>
                <td><?= number_format((float) $order['total_amount']) ?> VND</td>
                <td><?= htmlspecialchars((string) $order['status']) ?></td>
                <td><?= htmlspecialchars((string) $order['created_at']) ?></td>
                <td>
                    <a class="btn btn-sm btn-outline-primary" href="<?= app_url('/admin/orders/view') ?>?id=<?= (int) $order['id'] ?>">
                        Xem
                    </a>
                </td>
                <td style="min-width: 220px;">
                    <form method="post" action="<?= app_url('/admin/orders/update') ?>" class="d-flex gap-2 align-items-center">
                        <input type="hidden" name="id" value="<?= (int) $order['id'] ?>">
                        <select name="status" class="form-select form-select-sm">
                            <?php foreach ($statuses as $s): ?>
                                <option value="<?= $s ?>" <?= ((string) $order['status'] === (string) $s) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string) $s) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-sm btn-primary">Luu</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

