<h1 class="h3 mb-3">Dong bo AI/RAG</h1>

<div class="card">
    <div class="card-body">
        <p class="text-muted">
            He thong moi nen de n8n doc truc tiep du lieu tu Aiven MySQL qua view
            <code>ai_rag_documents</code>, sau do nap vao Supabase Vector Store.
        </p>

        <div class="alert alert-info">
            Hay chay file <code>database/rag_views.sql</code> tren Aiven MySQL, roi tao workflow sync trong n8n theo
            file <code>RAG_N8N_RENDER_AIVEN.md</code>. Cach nay khong can website gui toan bo du lieu sang n8n moi lan chat.
        </div>

        <?php if ($syncWebhookUrl === ''): ?>
            <div class="alert alert-secondary">
                Chua cau hinh webhook sync cu. Neu dang dung workflow n8n doc MySQL truc tiep thi co the bo qua nut nay.
            </div>
        <?php else: ?>
            <div class="small text-muted mb-3">
                Webhook sync cu: <code><?= htmlspecialchars($syncWebhookUrl) ?></code>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= app_url('/admin/ai-sync') ?>">
            <button class="btn btn-outline-primary" <?= $syncWebhookUrl === '' ? 'disabled' : '' ?>>
                Dong bo qua webhook cu
            </button>
        </form>
    </div>
</div>
