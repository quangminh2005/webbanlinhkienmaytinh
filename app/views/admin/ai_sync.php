<h1 class="h3 mb-3">Dong bo AI/RAG</h1>

<div class="card">
    <div class="card-body">
        <p class="text-muted">
            Chuc nang nay gui du lieu san pham, danh muc, khuyen mai va huong dan mua hang sang n8n de nap vao Supabase Vector Store.
        </p>

        <?php if ($syncWebhookUrl === ''): ?>
            <div class="alert alert-warning">
                Chua cau hinh <code>rag_sync_webhook_url</code> trong <code>config/chat.php</code>.
            </div>
        <?php else: ?>
            <div class="small text-muted mb-3">
                Webhook sync: <code><?= htmlspecialchars($syncWebhookUrl) ?></code>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= app_url('/admin/ai-sync') ?>">
            <button class="btn btn-primary" <?= $syncWebhookUrl === '' ? 'disabled' : '' ?>>
                Dong bo du lieu AI/RAG
            </button>
        </form>
    </div>
</div>
