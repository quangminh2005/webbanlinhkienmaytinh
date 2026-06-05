<h1 class="h3 mb-3">Dashboard & Analytics</h1>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Don hoan thanh</div>
                <div class="h4 mb-0"><?= (int) $summary['order_count'] ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Doanh thu</div>
                <div class="h4 mb-0"><?= number_format((float) $summary['revenue']) ?> VND</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Gia von da ban</div>
                <div class="h4 mb-0"><?= number_format((float) $summary['cost']) ?> VND</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Loi nhuan</div>
                <div class="h4 mb-0 text-success"><?= number_format((float) $summary['profit']) ?> VND</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Gia von hang ton</div>
                <div class="h4 mb-0"><?= number_format((float) $summary['inventory_cost']) ?> VND</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">San pham ban chay</h2>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                        <tr>
                            <th>San pham</th>
                            <th>Danh muc</th>
                            <th>Da ban</th>
                            <th>Doanh thu</th>
                            <th>Loi nhuan</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($topSellers)): ?>
                            <tr><td colspan="5" class="text-muted text-center">Chua co du lieu ban hang.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($topSellers as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) $item['name']) ?></td>
                                <td><?= htmlspecialchars((string) $item['category_name']) ?></td>
                                <td><?= (int) $item['sold_quantity'] ?></td>
                                <td><?= number_format((float) $item['revenue']) ?> VND</td>
                                <td><?= number_format((float) $item['profit']) ?> VND</td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Hang ton lau</h2>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                        <tr>
                            <th>San pham</th>
                            <th>Ton</th>
                            <th>So ngay</th>
                            <th>Gia von ton</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($deadstock)): ?>
                            <tr><td colspan="4" class="text-muted text-center">Chua co hang ton lau.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($deadstock as $item): ?>
                            <tr>
                                <td>
                                    <div><?= htmlspecialchars((string) $item['name']) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars((string) $item['category_name']) ?></div>
                                </td>
                                <td><?= (int) $item['stock_quantity'] ?></td>
                                <td><?= (int) $item['days_in_stock'] ?></td>
                                <td><?= number_format((float) $item['cost_price'] * (int) $item['stock_quantity']) ?> VND</td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="small text-muted">
                    Hang ton lau = san pham con ton kho va chua co don hoan thanh.
                </div>
            </div>
        </div>
    </div>
</div>
