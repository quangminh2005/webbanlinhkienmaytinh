<div class="row justify-content-center">
    <div class="col-md-6">
        <h1 class="h4 mb-3">Dang ky tai khoan</h1>
        <form method="post" action="<?= app_url('/auth/register') ?>">
            <div class="mb-3">
                <label class="form-label">Ho ten</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Mat khau</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button class="btn btn-success w-100">Tao tai khoan</button>
        </form>
    </div>
</div>

