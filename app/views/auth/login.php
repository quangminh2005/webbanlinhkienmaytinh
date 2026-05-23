<div class="row justify-content-center">
    <div class="col-md-5">
        <h1 class="h4 mb-3">Dang nhap</h1>
        <form method="post" action="<?= app_url('/auth/login') ?>">
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Mat khau</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button class="btn btn-primary w-100">Dang nhap</button>
        </form>
    </div>
</div>

