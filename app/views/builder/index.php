<h1 class="h3 mb-3">Build PC co ban</h1>

<form method="get" action="<?= app_url('/build-pc') ?>" class="row g-3">
    <div class="col-md-3">
        <label class="form-label">CPU</label>
        <select name="cpu" class="form-select">
            <option value="">-- Chon CPU --</option>
            <?php foreach ($cpus as $cpu): ?>
                <option value="<?= $cpu['id'] ?>" <?= ($selectedCpu && (int) $selectedCpu['id'] === (int) $cpu['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cpu['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Mainboard</label>
        <select name="main" class="form-select">
            <option value="">-- Chon Mainboard --</option>
            <?php foreach ($mainboards as $main): ?>
                <option value="<?= $main['id'] ?>" <?= ($selectedMain && (int) $selectedMain['id'] === (int) $main['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($main['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">VGA</label>
        <select name="vga" class="form-select">
            <option value="">-- Chon VGA --</option>
            <?php foreach ($vgaCards as $vga): ?>
                <option value="<?= $vga['id'] ?>" <?= ($selectedVga && (int) $selectedVga['id'] === (int) $vga['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($vga['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Nguon</label>
        <select name="psu" class="form-select">
            <option value="">-- Chon PSU --</option>
            <?php foreach ($psuList as $psu): ?>
                <option value="<?= $psu['id'] ?>" <?= ($selectedPsu && (int) $selectedPsu['id'] === (int) $psu['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($psu['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-12">
        <button class="btn btn-primary">Kiem tra tuong thich</button>
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
<?php elseif ($selectedCpu || $selectedMain || $selectedVga || $selectedPsu): ?>
    <div class="alert alert-success mt-4">Cau hinh co ban hien tai khong co xung dot lon.</div>
<?php endif; ?>

