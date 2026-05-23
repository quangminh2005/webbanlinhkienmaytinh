<div class="row">
    <div class="col-md-8">
        <h1 class="h3 mb-3">Chi tiet don #<?= (int) $order['id'] ?></h1>

        <div class="card mb-3">
            <div class="card-body">
                <div><strong>Trang thai:</strong> <?= htmlspecialchars((string) $order['status']) ?></div>
                <div><strong>Phuong thuc:</strong> <?= htmlspecialchars((string) ($order['payment_method'] ?? '')) ?></div>
                <div><strong>Tong:</strong> <span class="text-danger fw-bold"><?= number_format((float) $order['total_amount']) ?> VND</span></div>
                <div class="mt-2"><strong>Dia chi giao hang:</strong></div>
                <div class="border rounded p-2 bg-light">
                    <?= nl2br(htmlspecialchars((string) $order['shipping_address'])) ?>
                </div>

                <?php if ((string) $order['status'] === 'completed'): ?>
                    <form method="post" action="<?= app_url('/orders/return') ?>" class="mt-3">
                        <input type="hidden" name="id" value="<?= (int) $order['id'] ?>">
                        <button class="btn btn-outline-danger" onclick="return confirm('Ban muon yeu cau hoan tra don hang nay?')">
                            Yeu cau hoan tra
                        </button>
                    </form>
                <?php elseif ((string) $order['status'] === 'returned'): ?>
                    <div class="alert alert-info mt-3 mb-0">Don hang nay da duoc yeu cau hoan tra.</div>
                <?php endif; ?>
            </div>
        </div>

        <h2 class="h5 mb-2">Danh sach san pham</h2>
        <div class="table-responsive mb-4">
            <table class="table table-sm">
                <thead>
                <tr>
                    <th>San pham</th>
                    <th>SL</th>
                    <th>Don gia</th>
                    <th>Thanh tien</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($order['items'] as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $item['product_name']) ?></td>
                        <td><?= (int) $item['quantity'] ?></td>
                        <td><?= number_format((float) $item['unit_price']) ?></td>
                        <td><?= number_format((float) ((float) $item['unit_price']) * (int) $item['quantity']) ?> VND</td>
                    </tr>
                    <tr>
                        <td colspan="4">
                            <?php
                            $productId = (int) $item['product_id'];
                            $review = $reviews_by_product_id[$productId] ?? null;
                            $canReview = ((string) $order['status'] === 'completed' && !$review);
                            ?>

                            <?php if (!empty($review)): ?>
                                <div class="border rounded p-2 bg-white">
                                    <div><strong>Danh gia:</strong> <?= (int) $review['rating'] ?>/5</div>
                                    <div class="small text-muted"><?= htmlspecialchars((string) $review['created_at']) ?></div>
                                    <?php if (!empty($review['comment'])): ?>
                                        <div class="mt-2"><?= nl2br(htmlspecialchars((string) $review['comment'])) ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php elseif ($canReview): ?>
                                <div class="border rounded p-2 bg-light">
                                    <div class="small text-muted mb-2">Ban co the danh gia san pham nay.</div>
                                    <form method="post" action="<?= app_url('/reviews/create') ?>">
                                        <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                                        <input type="hidden" name="product_id" value="<?= (int) $productId ?>">

                                        <div class="mb-2">
                                            <label class="form-label small mb-1">Cham diem (1-5)</label>
                                            <select name="rating" class="form-select form-select-sm" required>
                                                <option value="">-- Chon --</option>
                                                <?php for ($r = 1; $r <= 5; $r++): ?>
                                                    <option value="<?= $r ?>"><?= $r ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small mb-1">Binh luan</label>
                                            <textarea name="comment" class="form-control form-control-sm" rows="2" placeholder="Chia se cam nhan..."></textarea>
                                        </div>
                                        <button class="btn btn-sm btn-primary">Gui danh gia</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <div class="text-muted small">Chua the danh gia (hoac da danh gia roi).</div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <a class="btn btn-outline-secondary" href="<?= app_url('/orders') ?>">Quay lai</a>
    </div>
</div>

