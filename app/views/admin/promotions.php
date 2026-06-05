<h1 class="h3 mb-3">CRM & Khuyen mai</h1>

<div class="alert alert-info small">
    Hang thanh vien tu dong theo tong tien don hoan thanh: Bac tu 10,000,000 VND giam 2%, Vang tu 50,000,000 VND giam 5%, Kim Cuong tu 100,000,000 VND giam 8%.
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h5">Tao ma giam gia</h2>
                <form method="post" action="<?= app_url('/admin/promotions/coupons/create') ?>" class="row g-2">
                    <div class="col-12"><input class="form-control" name="code" placeholder="Ma coupon" required></div>
                    <div class="col-6">
                        <select class="form-select" name="discount_type">
                            <option value="percent">Phan tram</option>
                            <option value="fixed">So tien co dinh</option>
                        </select>
                    </div>
                    <div class="col-6"><input class="form-control" type="number" step="0.01" name="discount_value" placeholder="Gia tri" required></div>
                    <div class="col-12"><input class="form-control" type="number" step="0.01" name="min_order_amount" placeholder="Don toi thieu"></div>
                    <div class="col-6"><input class="form-control" type="datetime-local" name="starts_at"></div>
                    <div class="col-6"><input class="form-control" type="datetime-local" name="ends_at"></div>
                    <div class="col-12 form-check ms-2">
                        <input class="form-check-input" type="checkbox" name="active" id="coupon-active" checked>
                        <label class="form-check-label" for="coupon-active">Dang bat</label>
                    </div>
                    <div class="col-12"><button class="btn btn-primary w-100">Tao coupon</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h5">Tao combo</h2>
                <form method="post" action="<?= app_url('/admin/promotions/combos/create') ?>" class="row g-2">
                    <div class="col-12"><input class="form-control" name="name" placeholder="Ten combo" required></div>
                    <div class="col-6">
                        <select class="form-select" name="category_a_id">
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6">
                        <select class="form-select" name="category_b_id">
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12"><input class="form-control" type="number" step="0.01" name="discount_amount" placeholder="So tien giam" required></div>
                    <div class="col-12 form-check ms-2">
                        <input class="form-check-input" type="checkbox" name="active" id="combo-active" checked>
                        <label class="form-check-label" for="combo-active">Dang bat</label>
                    </div>
                    <div class="col-12"><button class="btn btn-primary w-100">Tao combo</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h5">Tao flash sale</h2>
                <form method="post" action="<?= app_url('/admin/promotions/flash-sales/create') ?>" class="row g-2">
                    <div class="col-12">
                        <select class="form-select" name="product_id">
                            <?php foreach ($products as $product): ?>
                                <option value="<?= $product['id'] ?>"><?= htmlspecialchars($product['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6">
                        <select class="form-select" name="discount_type">
                            <option value="percent">Phan tram</option>
                            <option value="fixed">So tien co dinh</option>
                        </select>
                    </div>
                    <div class="col-6"><input class="form-control" type="number" step="0.01" name="discount_value" placeholder="Gia tri" required></div>
                    <div class="col-6"><input class="form-control" type="datetime-local" name="starts_at"></div>
                    <div class="col-6"><input class="form-control" type="datetime-local" name="ends_at"></div>
                    <div class="col-12 form-check ms-2">
                        <input class="form-check-input" type="checkbox" name="active" id="flash-active" checked>
                        <label class="form-check-label" for="flash-active">Dang bat</label>
                    </div>
                    <div class="col-12"><button class="btn btn-primary w-100">Tao flash sale</button></div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-lg-4">
        <h2 class="h5">Coupon</h2>
        <table class="table table-sm bg-white">
            <tbody>
            <?php foreach ($coupons as $coupon): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($coupon['code']) ?></strong>
                        <div class="small text-muted"><?= htmlspecialchars($coupon['discount_type']) ?>: <?= number_format((float) $coupon['discount_value']) ?></div>
                    </td>
                    <td class="text-end"><?= (int) $coupon['active'] ? 'Bat' : 'Tat' ?></td>
                    <td class="text-end">
                        <form method="post" action="<?= app_url('/admin/promotions/delete') ?>">
                            <input type="hidden" name="type" value="coupon">
                            <input type="hidden" name="id" value="<?= $coupon['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger">Xoa</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="col-lg-4">
        <h2 class="h5">Combo</h2>
        <table class="table table-sm bg-white">
            <tbody>
            <?php foreach ($combos as $combo): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($combo['name']) ?></strong>
                        <div class="small text-muted"><?= htmlspecialchars($combo['category_a_name']) ?> + <?= htmlspecialchars($combo['category_b_name']) ?></div>
                    </td>
                    <td><?= number_format((float) $combo['discount_amount']) ?></td>
                    <td class="text-end">
                        <form method="post" action="<?= app_url('/admin/promotions/delete') ?>">
                            <input type="hidden" name="type" value="combo">
                            <input type="hidden" name="id" value="<?= $combo['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger">Xoa</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="col-lg-4">
        <h2 class="h5">Flash sale</h2>
        <table class="table table-sm bg-white">
            <tbody>
            <?php foreach ($flashSales as $sale): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($sale['product_name']) ?></strong>
                        <div class="small text-muted"><?= htmlspecialchars($sale['discount_type']) ?>: <?= number_format((float) $sale['discount_value']) ?></div>
                    </td>
                    <td class="text-end">
                        <form method="post" action="<?= app_url('/admin/promotions/delete') ?>">
                            <input type="hidden" name="type" value="flash">
                            <input type="hidden" name="id" value="<?= $sale['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger">Xoa</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
