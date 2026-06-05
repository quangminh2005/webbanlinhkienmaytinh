<h1 class="h3 mb-3">Admin - Quan ly san pham</h1>
<p class="text-muted small">
    Khi tang ton kho, nhap gia von cua lo hang moi; he thong se tu tinh gia von binh quan.
</p>

<div class="card mb-4">
    <div class="card-body">
        <h2 class="h5">Tim kiem va loc</h2>
        <form method="get" action="<?= app_url('/admin/products') ?>" class="row g-2">
            <div class="col-md-5">
                <input
                    class="form-control"
                    name="keyword"
                    placeholder="Tim theo ten san pham..."
                    value="<?= htmlspecialchars((string) ($filters['keyword'] ?? '')) ?>"
                >
            </div>
            <div class="col-md-4">
                <select class="form-select" name="category_id">
                    <option value="">Tat ca danh muc</option>
                    <?php foreach ($categories as $category): ?>
                        <option
                            value="<?= $category['id'] ?>"
                            <?= ((int) ($filters['category_id'] ?? 0) === (int) $category['id']) ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-grid">
                <button class="btn btn-primary">Ap dung</button>
            </div>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h2 class="h5">Them san pham</h2>
        <form method="post" action="<?= app_url('/admin/products/create') ?>" enctype="multipart/form-data" class="row g-2">
            <div class="col-md-4">
                <input class="form-control" name="name" placeholder="Ten san pham" required>
            </div>
            <div class="col-md-2">
                <input class="form-control" name="price" type="number" step="0.01" placeholder="Gia" required>
            </div>
            <div class="col-md-2">
                <input class="form-control" name="cost_price" type="number" step="0.01" placeholder="Gia von">
            </div>
            <div class="col-md-2">
                <input class="form-control" name="stock_quantity" type="number" placeholder="Ton kho" required>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="category_id" required>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <input class="form-control" name="socket" placeholder="Socket">
            </div>
            <div class="col-md-2">
                <input class="form-control" name="ram_type" placeholder="RAM type">
            </div>
            <div class="col-md-2">
                <input class="form-control" name="vram_gb" type="number" placeholder="VRAM">
            </div>
            <div class="col-md-2">
                <input class="form-control" name="wattage" type="number" placeholder="Wattage">
            </div>
            <div class="col-md-3">
                <input class="form-control" name="image_url" placeholder="Image URL">
            </div>
            <div class="col-md-3">
                <input class="form-control" type="file" name="image_file" accept="image/*">
            </div>
            <div class="col-12">
                <textarea class="form-control" name="description" rows="2" placeholder="Mo ta"></textarea>
            </div>
            <div class="col-12">
                <button class="btn btn-success">Them moi</button>
            </div>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h2 class="h5">Import san pham tu CSV</h2>
        <p class="text-muted small mb-2">
            Cot bat buoc: <code>name</code>, <code>category_slug</code>, <code>price</code>, <code>stock_quantity</code>.
            Cot tuy chon: <code>category_name</code>, <code>cost_price</code>, <code>description</code>, <code>image_url</code>, <code>socket</code>,
            <code>ram_type</code>, <code>vram_gb</code>, <code>wattage</code>.
        </p>
        <form method="post" action="<?= app_url('/admin/products/import') ?>" enctype="multipart/form-data" class="row g-2">
            <div class="col-md-8">
                <input type="file" name="products_csv" accept=".csv,text/csv" class="form-control" required>
            </div>
            <div class="col-md-4">
                <button class="btn btn-primary w-100">Import CSV</button>
            </div>
        </form>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-bordered table-sm bg-white">
        <thead>
        <tr>
            <th>ID</th>
            <th>Ten</th>
            <th>Gia</th>
            <th>Gia von</th>
            <th>Ton</th>
            <th>Danh muc</th>
            <th>Anh</th>
            <th>Tuong thich</th>
            <th>Hanh dong</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($products)): ?>
            <tr>
                <td colspan="9" class="text-center text-muted">Khong co du lieu.</td>
            </tr>
        <?php endif; ?>
        <?php foreach ($products as $product): ?>
            <tr>
                <td><?= $product['id'] ?></td>
                <td>
                    <input form="update-<?= $product['id'] ?>" class="form-control form-control-sm" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
                    <textarea form="update-<?= $product['id'] ?>" class="form-control form-control-sm mt-1" name="description" rows="1" placeholder="Mo ta"><?= htmlspecialchars((string) $product['description']) ?></textarea>
                </td>
                <td>
                    <input form="update-<?= $product['id'] ?>" class="form-control form-control-sm" type="number" step="0.01" name="price" value="<?= (float) $product['price'] ?>" required>
                </td>
                <td>
                    <input form="update-<?= $product['id'] ?>" class="form-control form-control-sm" type="number" step="0.01" name="cost_price" value="<?= (float) ($product['cost_price'] ?? 0) ?>">
                </td>
                <td>
                    <input form="update-<?= $product['id'] ?>" class="form-control form-control-sm" type="number" name="stock_quantity" value="<?= (int) $product['stock_quantity'] ?>" required>
                </td>
                <td>
                    <select form="update-<?= $product['id'] ?>" class="form-select form-select-sm" name="category_id" required>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" <?= ((int) $product['category_id'] === (int) $category['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input form="update-<?= $product['id'] ?>" class="form-control form-control-sm mt-1" name="image_url" value="<?= htmlspecialchars((string) $product['image_url']) ?>" placeholder="Image URL">
                </td>
                <td style="min-width: 120px;">
                    <?php if (!empty($product['image_url'])): ?>
                        <img src="<?= htmlspecialchars(product_image_url((string) $product['image_url'])) ?>" alt="product" class="img-thumbnail mb-1" style="max-width: 90px; max-height: 70px;">
                    <?php endif; ?>
                    <input form="update-<?= $product['id'] ?>" class="form-control form-control-sm" type="file" name="image_file" accept="image/*">
                </td>
                <td>
                    <input form="update-<?= $product['id'] ?>" class="form-control form-control-sm mb-1" name="socket" value="<?= htmlspecialchars((string) $product['socket']) ?>" placeholder="Socket">
                    <input form="update-<?= $product['id'] ?>" class="form-control form-control-sm mb-1" name="ram_type" value="<?= htmlspecialchars((string) $product['ram_type']) ?>" placeholder="RAM type">
                    <div class="d-flex gap-1">
                        <input form="update-<?= $product['id'] ?>" class="form-control form-control-sm" type="number" name="vram_gb" value="<?= (int) $product['vram_gb'] ?>" placeholder="VRAM">
                        <input form="update-<?= $product['id'] ?>" class="form-control form-control-sm" type="number" name="wattage" value="<?= (int) $product['wattage'] ?>" placeholder="Watt">
                    </div>
                </td>
                <td style="min-width: 170px;">
                <form id="update-<?= $product['id'] ?>" method="post" action="<?= app_url('/admin/products/update') ?>" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?= $product['id'] ?>">
                    <input type="hidden" name="existing_image_url" value="<?= htmlspecialchars((string) $product['image_url']) ?>">
                    <button class="btn btn-sm btn-primary w-100 mb-1">Luu</button>
                </form>
                <form method="post" action="<?= app_url('/admin/products/delete') ?>" onsubmit="return confirm('Ban chac chan muon xoa san pham nay?')">
                    <input type="hidden" name="id" value="<?= $product['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger w-100">Xoa</button>
                </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-between align-items-center mt-3">
    <div class="text-muted small">
        Tong: <?= (int) ($filters['total'] ?? 0) ?> san pham
    </div>
    <?php if ((int) ($filters['total_pages'] ?? 1) > 1): ?>
        <?php
        $currentPage = (int) $filters['page'];
        $totalPages = (int) $filters['total_pages'];
        $keyword = (string) ($filters['keyword'] ?? '');
        $categoryId = (string) ($filters['category_id'] ?? '');
        ?>
        <nav>
            <ul class="pagination mb-0">
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                        <a
                            class="page-link"
                            href="<?= app_url('/admin/products') ?>?page=<?= $p ?>&keyword=<?= urlencode($keyword) ?>&category_id=<?= urlencode($categoryId) ?>"
                        >
                            <?= $p ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

