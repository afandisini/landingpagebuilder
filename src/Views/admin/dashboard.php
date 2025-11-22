<style>
  /* biar rapi di semua breakpoint */
  .table td, .table th { vertical-align: middle; }
  .truncate { max-width: 140px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3">Dashboard</h1>
</div>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
        <div class="card border-primary">
            <div class="card-body d-flex justify-content-between align-items-center">
                
                <div>
                    <h5 class="card-title mb-1">Total Pengunjung</h5>
                    <p class="card-text h2 mb-0">
                        <?= number_format($stats['total_views'] ?? 0); ?>
                    </p>
                </div>

                <div class="text-primary" style="--bs-text-opacity: .5; font-size:3rem; line-height:1;">
                    <i class="bi bi-people"></i>
                </div>

            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-success">
            <div class="card-body d-flex justify-content-between align-items-center">
                
                <div>
                    <h5 class="card-title mb-1">Total Halaman</h5>
                    <p class="card-text h2 mb-0">
                        <?= number_format($stats['total_pages_tracked'] ?? 0); ?>
                    </p>
                </div>

                <div class="text-success" style="--bs-text-opacity: .5; font-size:3rem; line-height:1;">
                    <i class="bi bi-file-earmark-text"></i>
                </div>

            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-warning">
            <div class="card-body d-flex justify-content-between align-items-center">

                <div>
                    <h5 class="card-title mb-1">Pengunjung Hari Ini</h5>
                    <p class="card-text h2 mb-0">
                        <?= number_format($stats['today_views'] ?? 0); ?>
                    </p>
                </div>

                <div class="text-warning" style="--bs-text-opacity: .5; font-size:3rem; line-height:1;">
                    <i class="bi bi-bar-chart-line"></i>
                </div>

            </div>
        </div>
    </div>
</div>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5">Latest Pages</h2>
    <a href="?r=admin/pages/create" class="btn btn-sm btn-primary"><i class="bi bi-file-earmark me-1"></i> Buat Halaman</a>
</div>

<div class="table-responsive-sm"><!-- scroll hanya untuk <576px -->
  <table class="table table-striped table-sm align-middle">
    <thead>
      <tr>
        <th class="text-nowrap">#</th>
        <th>Title</th>
        <th class="d-none d-md-table-cell">Slug</th>          <!-- sembunyi di < md -->
        <th class="text-nowrap">Status</th>
        <th class="d-none d-sm-table-cell">Published URL</th> <!-- sembunyi di < sm -->
      </tr>
    </thead>
    <tbody>
    <?php foreach ($pages as $page): ?>
      <tr>
        <td class="text-muted"><?= (int)$page['id']; ?></td>

        <td class="truncate" title="<?= htmlspecialchars($page['title']); ?>">
          <?= htmlspecialchars($page['title']); ?>
        </td>

        <td class="d-none d-md-table-cell truncate" title="<?= htmlspecialchars($page['slug']); ?>">
          <?= htmlspecialchars($page['slug']); ?>
        </td>

        <td>
          <?php 
            $status  = $page['status'] ?? '';
            $cls = ($status === 'published') ? 'badge text-bg-success' : 'badge text-bg-warning';
          ?>
          <span class="<?= $cls; ?>"><?= htmlspecialchars($status); ?></span>
        </td>

        <td class="d-none d-sm-table-cell">
          <div class="btn-group btn-group-sm">
            <span class="btn btn-secondary disabled">
              <i class="bi bi-eye me-1"></i><?= shortNumber($pageViewCounts[$page['id']] ?? 0); ?>
            </span>
            <?php if (!empty($page['published_path'])): ?>
              <a class="btn btn-success" 
                 href="<?= $baseUrl . '/' . ltrim($page['published_path'], '/'); ?>" target="_blank">
                <i class="bi bi-box-arrow-up-right me-1"></i>Lihat
              </a>
            <?php else: ?>
              <span class="btn btn-secondary disabled">-</span>
            <?php endif; ?>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
