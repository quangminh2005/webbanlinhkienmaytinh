<h1 class="h3 mb-3">Build PC</h1>

<form method="get" action="<?= app_url('/build-pc') ?>" class="row g-3">
    <?php foreach ($parts as $key => $part): ?>
        <div class="col-md-4">
            <label class="form-label"><?= htmlspecialchars((string) $part['label']) ?></label>
            <select name="<?= htmlspecialchars((string) $key) ?>" class="form-select">
                <option value="">-- Chon <?= htmlspecialchars((string) $part['label']) ?> --</option>
                <?php foreach ($options[$key] ?? [] as $product): ?>
                    <?php
                    $stock = (int) $product['stock_quantity'];
                    $isSelected = !empty($selected[$key]) && (int) $selected[$key]['id'] === (int) $product['id'];
                    ?>
                    <option value="<?= (int) $product['id'] ?>" <?= $isSelected ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) $product['name']) ?>
                        - <?= number_format((float) $product['price']) ?> VND
                        - Ton: <?= $stock ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endforeach; ?>

    <div class="col-12">
        <button class="btn btn-primary">Kiem tra cau hinh</button>
    </div>
</form>

<?php if (!empty($warnings)): ?>
    <div class="alert alert-warning mt-4">
        <strong>Canh bao:</strong>
        <ul class="mb-0">
            <?php foreach ($warnings as $warning): ?>
                <li><?= htmlspecialchars($warning) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php elseif (!empty($selectedItems)): ?>
    <div class="alert alert-success mt-4">Cau hinh hien tai khong co xung dot lon.</div>
<?php endif; ?>

<?php if (!empty($selectedItems)): ?>
    <div class="card mt-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                <h2 class="h5 mb-0">Cau hinh da chon</h2>
                <div class="fw-bold text-danger">Tong: <?= number_format((float) $total) ?> VND</div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                    <tr>
                        <th>Linh kien</th>
                        <th>San pham</th>
                        <th>Gia</th>
                        <th>Ton kho</th>
                        <th>Trang thai</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($selectedItems as $key => $product): ?>
                        <?php $stock = (int) $product['stock_quantity']; ?>
                        <tr>
                            <td><?= htmlspecialchars((string) ($parts[$key]['label'] ?? $key)) ?></td>
                            <td><?= htmlspecialchars((string) $product['name']) ?></td>
                            <td><?= number_format((float) $product['price']) ?> VND</td>
                            <td><?= $stock ?></td>
                            <td>
                                <?php if ($stock > 0): ?>
                                    <span class="badge text-bg-success">Con hang</span>
                                <?php else: ?>
                                    <span class="badge text-bg-danger">Het hang</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($outOfStock)): ?>
                <div class="alert alert-danger mb-3">
                    Chi cac linh kien con ton kho moi co the them vao gio hang.
                </div>
            <?php endif; ?>

            <form method="post" action="<?= app_url('/build-pc/add-to-cart') ?>">
                <?php foreach ($selectedItems as $key => $product): ?>
                    <input type="hidden" name="<?= htmlspecialchars((string) $key) ?>" value="<?= (int) $product['id'] ?>">
                <?php endforeach; ?>
                <button class="btn btn-success">Them linh kien con hang vao gio hang</button>
            </form>
        </div>
    </div>
<?php endif; ?>
