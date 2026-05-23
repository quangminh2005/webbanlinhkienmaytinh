<div class="row">
    <div class="col-md-7">
        <h1 class="h3 mb-3">Thanh toan</h1>

        <form method="post" action="<?= app_url('/checkout') ?>">
            <div class="mb-3">
                <label class="form-label">Dia chi giao hang</label>
                <textarea class="form-control" name="shipping_address" rows="4" required></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Phuong thuc thanh toan</label>
                <select class="form-select" name="payment_method" required>
                    <option value="cod">COD - Thanh toan khi nhan hang</option>
                    <option value="bank_transfer">Chuyen khoan/Bank transfer</option>
                </select>
            </div>

            <button class="btn btn-success w-100">Dat hang</button>
        </form>
    </div>

    <div class="col-md-5">
        <h2 class="h5">Don hang</h2>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                <tr>
                    <th>San pham</th>
                    <th>SL</th>
                    <th>Thanh tien</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['product']['name']) ?></td>
                        <td><?= (int) $item['qty'] ?></td>
                        <td><?= number_format((float) $item['line_total']) ?> VND</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="text-end">
            <div class="text-muted">Tong</div>
            <div class="fs-5 text-danger fw-bold"><?= number_format((float) $total) ?> VND</div>
        </div>
    </div>
</div>

