        </main>

        <aside class="col-lg-2 d-none d-lg-flex flex-column align-items-stretch border-start bg-white py-4 px-2">

            <div class="sticky-top pt-1" style="top: 1rem;">

                <?php if (!empty($bannerRightUrl)): ?>

                    <a href="<?= app_url('/cart') ?>" class="layout-banner-link shadow-sm mb-3 text-decoration-none bg-light">

                        <img

                            src="<?= htmlspecialchars($bannerRightUrl) ?>"

                            alt="Quang cao"

                            loading="lazy"

                        >

                    </a>

                <?php else: ?>

                    <div class="rounded-3 border border-dashed bg-light p-3 small text-muted text-center mb-3">

                        Banner phai: <code class="small">banner-phai.png</code> hoac <code>banner-right.jpg</code>

                    </div>

                <?php endif; ?>

                <div class="rounded-3 border bg-light p-3 small text-muted text-center">

                    <div class="fw-semibold text-dark mb-2">Hotline</div>

                    <div>034 969 4556</div>

                    <div class="mt-2">8:00 — 21:00</div>

                </div>

            </div>

        </aside>

    </div>

</div>



<footer class="bg-dark text-light mt-auto">

    <div class="container py-4">

        <div class="row g-4 align-items-start">

            <div class="col-md-4">

                <div class="d-flex align-items-center gap-2 mb-2">

                    <?php if (!empty($bannerLogoUrl)): ?>

                        <img src="<?= htmlspecialchars($bannerLogoUrl) ?>" alt="" height="40" class="d-inline-block" style="max-height: 40px; width: auto;">

                    <?php endif; ?>

                    <span class="fw-semibold">PC Parts Shop</span>

                </div>

                <p class="small text-secondary mb-0">

                    Chuyen linh kien may tinh, VGA, CPU, RAM va phu kien. Giao hang toan quoc.

                </p>

            </div>

            <div class="col-md-4">

                <div class="fw-semibold mb-2">Lien ket</div>

                <ul class="list-unstyled small mb-0">

                    <li><a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover" href="<?= app_url('/') ?>">San pham</a></li>

                    <li><a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover" href="<?= app_url('/build-pc') ?>">Build PC</a></li>

                    <li><a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover" href="<?= app_url('/cart') ?>">Gio hang</a></li>

                </ul>

            </div>

            <div class="col-md-4">

                <div class="fw-semibold mb-2">Lien he</div>

                <p class="small text-secondary mb-0">

                    Email: quangminhngo41@gmail.com<br>

                    Dia chi: Viet Nam

                </p>

            </div>

        </div>

        <hr class="border-secondary my-3">

        <p class="small text-secondary text-center mb-0">&copy; <?= date('Y') ?> PC Parts Shop. Ban quyen thuoc ve Ngo Quang Minh.</p>

    </div>

</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php require __DIR__ . '/../partials/chat-widget.php'; ?>

</body>

</html>



