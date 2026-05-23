<div class="row justify-content-center">
    <div class="col-md-8 col-lg-7">
        <h1 class="h3 mb-3">Thong tin ca nhan</h1>

        <div class="card">
            <div class="card-body">
                <form method="post" action="<?= app_url('/profile') ?>">
                    <div class="mb-3">
                        <label class="form-label">Ho ten</label>
                        <input
                            type="text"
                            name="name"
                            class="form-control"
                            value="<?= htmlspecialchars((string) ($user['name'] ?? '')) ?>"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input
                            type="email"
                            name="email"
                            class="form-control"
                            value="<?= htmlspecialchars((string) ($user['email'] ?? '')) ?>"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">So dien thoai</label>
                        <input
                            type="text"
                            name="phone"
                            class="form-control"
                            value="<?= htmlspecialchars((string) ($user['phone'] ?? '')) ?>"
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Dia chi</label>
                        <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars((string) ($user['address'] ?? '')) ?></textarea>
                    </div>

                    <button class="btn btn-primary">Luu thay doi</button>
                </form>
            </div>
        </div>
    </div>
</div>
