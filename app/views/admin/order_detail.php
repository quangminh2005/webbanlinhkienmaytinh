<div class="d-flex justify-content-between align-items-center gap-3 mb-3">
    <h1 class="h3 mb-0">Chi tiet don #<?= (int) $order['id'] ?></h1>
    <a class="btn btn-outline-secondary btn-sm" href="<?= app_url('/admin/orders') ?>">Quay lai</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Thong tin don hang</h2>
                <div class="mb-2"><strong>Trang thai:</strong> <?= htmlspecialchars((string) $order['status']) ?></div>
                <div class="mb-2"><strong>Thanh toan:</strong> <?= htmlspecialchars((string) ($order['payment_method'] ?? '')) ?> / <?= htmlspecialchars((string) ($order['payment_status'] ?? '')) ?></div>
                <div class="mb-2"><strong>Thoi gian:</strong> <?= htmlspecialchars((string) $order['created_at']) ?></div>
                <div><strong>Tong tien:</strong> <span class="text-danger fw-bold"><?= number_format((float) $order['total_amount']) ?> VND</span></div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Thong tin khach hang</h2>
                <div class="mb-2"><strong>Ho ten:</strong> <?= htmlspecialchars((string) $order['user_name']) ?></div>
                <div class="mb-2"><strong>Email:</strong> <?= htmlspecialchars((string) $order['user_email']) ?></div>
                <div class="mb-2"><strong>So dien thoai:</strong> <?= htmlspecialchars((string) ($order['user_phone'] ?: 'Chua cap nhat')) ?></div>
                <div><strong>Dia chi tai khoan:</strong></div>
                <div class="border rounded p-2 bg-light mt-1">
                    <?= nl2br(htmlspecialchars((string) ($order['user_address'] ?: 'Chua cap nhat'))) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h2 class="h5 mb-2">Dia chi giao hang</h2>
        <div class="border rounded p-2 bg-light">
            <?= nl2br(htmlspecialchars((string) $order['shipping_address'])) ?>
        </div>
    </div>
</div>

<h2 class="h5 mb-2">San pham va danh gia</h2>
<div class="table-responsive">
    <table class="table table-bordered table-sm bg-white align-middle">
        <thead>
        <tr>
            <th>San pham</th>
            <th>SL</th>
            <th>Don gia</th>
            <th>Thanh tien</th>
            <th>Danh gia</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($order['items'] as $item): ?>
            <?php
            $productId = (int) $item['product_id'];
            $review = $reviews_by_product_id[$productId] ?? null;
            ?>
            <tr>
                <td><?= htmlspecialchars((string) $item['product_name']) ?></td>
                <td><?= (int) $item['quantity'] ?></td>
                <td><?= number_format((float) $item['unit_price']) ?> VND</td>
                <td><?= number_format((float) ((float) $item['unit_price'] * (int) $item['quantity'])) ?> VND</td>
                <td style="min-width: 260px;">
                    <?php if ($review): ?>
                        <div><strong><?= (int) $review['rating'] ?>/5</strong></div>
                        <div class="small text-muted"><?= htmlspecialchars((string) $review['created_at']) ?></div>
                        <?php if (!empty($review['comment'])): ?>
                            <div class="mt-1"><?= nl2br(htmlspecialchars((string) $review['comment'])) ?></div>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="text-muted">Chua co danh gia</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
