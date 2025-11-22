<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Pilih Template Landing Page</h1>
    <a href="?r=admin/pages" class="btn btn-sm btn-outline-secondary">Kembali ke Pages</a>
</div>
<?php if (empty($templates)): ?>
    <div class="alert alert-warning">
        Belum ada template yang tersedia. Silakan tambah template terlebih dahulu di database.
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($templates as $template): ?>
            <?php
            $badgeType = 'Informasi saja';
            if ($template['order_type'] === 'link') {
                $badgeType = 'CTA Link';
            } elseif ($template['order_type'] === 'gateway') {
                $badgeType = 'Payment Gateway';
            }
            $thumbnailPath = '';
            if (!empty($template['thumbnail'])) {
                $thumbnailPath = filter_var($template['thumbnail'], FILTER_VALIDATE_URL)
                    ? $template['thumbnail']
                    : $baseUrl . '/' . ltrim($template['thumbnail'], '/');
            }
            ?>
            <div class="col-md-6 col-lg-6">
                <div class="card rounded-3 flex-row h-100 shadow-sm overflow-hidden">
                    <?php if ($thumbnailPath): ?>
                        <div class="col-5">
                            <img 
                                src="<?php echo htmlspecialchars($thumbnailPath); ?>" 
                                alt="<?php echo htmlspecialchars($template['name']); ?>" 
                                class="img-fluid h-100 w-100 object-fit-cover"
                                style="object-fit: cover;"
                            >
                        </div>
                    <?php endif; ?>

                    <div class="col-7 p-3">
                        <h5 class="card-title mb-1">
                            <?php echo htmlspecialchars($template['name']); ?>
                        </h5>

                        <p class="text-muted text-sm mb-2" style="min-height: 3rem;">
                            <?php echo nl2br(htmlspecialchars($template['description'] ?? '')); ?>
                        </p>

                        <span class="badge bg-secondary mb-3">
                            <?php echo htmlspecialchars($badgeType); ?>
                        </span>

                        <form method="post" action="?r=admin/pages/template" class="mt-auto">
                            <input type="hidden" name="_csrf" value="<?php echo htmlspecialchars(Csrf::token()); ?>">
                            <input type="hidden" name="template_id" 
                                value="<?php echo htmlspecialchars($template['id']); ?>">
                            <button type="submit" class="btn btn-primary">
                                Gunakan Template ini
                            </button>
                        </form>
                    </div>

                </div>
            </div>

        <?php endforeach; ?>
    </div>
<?php endif; ?>
