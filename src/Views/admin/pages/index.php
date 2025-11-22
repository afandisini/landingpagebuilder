<style>
    .table td, .table th {
        vertical-align: middle;
    }
</style>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3">Landing Pages & Payment Getway</h1>
    <a href="?r=admin/pages/create" class="btn btn-sm btn-primary"><i class="bi bi-code-slash me-1"></i>Buat Halaman</a>
</div>
<div class="table-responsive">
    <table class="table table-bordered align-middle">
        <thead>
        <tr>
            <th>#</th>
            <th>Title</th>
            <th>Slug</th>
            <th>Status</th>
            <th>Published URL</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($pages as $page): ?>
            <tr>
                <td><?= htmlspecialchars($page['id']); ?></td>
                <td class="fw-bold"><?= htmlspecialchars($page['title']); ?></td>
                <td><?= htmlspecialchars($page['slug']); ?></td>
                <td>
                    <?php 
                        $status  = $page['status'] ?? '';
                        $cls = ($status === 'published') ? 'badge text-bg-success' : 'badge text-bg-warning';
                    ?>
                    <span class="<?= $cls; ?>"><?= htmlspecialchars($status); ?></span>
                </td>
                <td>
                    <?php
                        $status = $page['status'] ?? '';
                        $isPublished = ($status === 'published');
                        $hasPath = !empty($page['published_path']);
                        $badgeCls = $isPublished ? 'badge text-bg-success' : 'badge text-bg-warning';
                        ?>

                    <?php if ($isPublished && $hasPath): ?>
                        <a class="btn btn-primary btn-sm"
                            href="<?= $baseUrl . '/' . ltrim($page['published_path'], '/'); ?>"
                            target="_blank">
                            <i class="bi bi-box-arrow-up-right"></i> Lihat Halaman
                        </a>
                    <?php elseif ($isPublished && !$hasPath): ?>
                        <!-- Published tapi belum punya file/URL -->
                        <a class="btn btn-secondary btn-sm disabled" role="button" aria-disabled="true" tabindex="-1"
                            title="Status published, tapi published_path kosong.">
                            <i class="bi bi-exclamation-triangle"></i> Path kosong
                        </a>
                    <?php else: ?>
                        <!-- Belum published -->
                        <a class="btn btn-secondary btn-sm disabled" role="button" aria-disabled="true" tabindex="-1">
                            <i class="bi bi-ban"></i> Belum Dipublikasikan
                        </a>
                    <?php endif; ?>
                </td>
                <td class="text-nowrap">
                    <a href="?r=admin/pages/edit&id=<?= $page['id']; ?>" class="btn btn-sm btn-secondary"><i class="bi bi-pencil"></i></a>
                    <form action="?r=admin/pages/delete" method="post" class="d-inline" onsubmit="return confirm('Hapus halaman ini?');">
                        <input type="hidden" name="id" value="<?= $page['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash3"></i></button>
                    </form>
                    <form action="?r=admin/pages/publish" method="post" class="d-inline">
                        <input type="hidden" name="id" value="<?= $page['id']; ?>">
                        <?php
                            $status = $page['status'] ?? '';
                            $icon  = ($status === 'published') ? 'bi-check2' : 'bi-cloud-upload';
                            $color  = ($status === 'published') ? 'btn-success disabled' : 'btn-warning';
                            $text  = ($status === 'published') ? 'Publish' : 'Pilih';
                        ?>
                        <button type="submit" class="btn btn-sm <?= $color;?>">
                            <i class="bi <?= $icon; ?>"></i> <?= $text; ?>
                        </button>
                    </form>                 
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
