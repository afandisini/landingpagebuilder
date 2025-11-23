<?php
// --- bootstrap minimal ---
date_default_timezone_set('Asia/Jakarta');

// TODO: adjust DSN/cred to your env
$pdo = new PDO('mysql:host=127.0.0.1;dbname=landingpagebuilder;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

function slugify($text)
{
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    return strtolower($text ?: 'page');
}

function ensureUniqueSlug(PDO $pdo, $base, $excludeId = null)
{
    $slug = $base;
    $i = 2;
    while (true) {
        $sql = 'SELECT id FROM pages WHERE slug = ?' . ($excludeId ? ' AND id != ?' : '');
        $stmt = $pdo->prepare($sql);
        $stmt->execute($excludeId ? [$slug, $excludeId] : [$slug]);
        if (!$stmt->fetch()) {
            return $slug;
        }
        $slug = $base . '-' . $i++;
    }
}

function sanitizeHtml($html)
{
    $html = preg_replace('#<div class="lpb-toolbar">.*?</div>#si', '', $html);
    $html = preg_replace('#<div class="lpb-handle">.*?</div>#si', '', $html);
    $html = preg_replace('/\sdraggable="[^"]*"/i', '', $html);
    $html = preg_replace("/\sdraggable='[^']*'/i", '', $html);
    $html = preg_replace('/\scontenteditable="[^"]*"/i', '', $html);
    $html = preg_replace("/\scontenteditable='[^']*'/i", '', $html);
    $html = preg_replace('/\son[a-z]+\s*=\s*"[^"]*"/i', '', $html);
    $html = preg_replace("/\son[a-z]+\s*=\s*'[^']*'/i", '', $html);
    $html = preg_replace_callback('/class="([^"]*)"/i', function ($m) {
        $classes = array_filter(array_map('trim', explode(' ', $m[1])), fn($c) => !preg_match('/^lpb-/', $c));
        return count($classes) ? 'class="' . implode(' ', $classes) . '"' : '';
    }, $html);

    // Remove builder-only controls (add/remove buttons, modals)
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $xpath = new DOMXPath($dom);
    $queries = [
        '//*[@data-layout-key]',
        '//*[@data-action="remove-layout"]',
        '//*[@data-action="confirm-remove-layout"]',
        '//*[@data-builder-only]',
        '//*[@id="TambahLayout"]',
        '//*[@id="hapusLayoutCard"]',
        '//*[@data-bs-target="#TambahLayout"]',
        '//*[@data-bs-target="#hapusLayoutCard"]'
    ];
    foreach ($queries as $q) {
        foreach ($xpath->query($q) as $node) {
            if ($node->parentNode) {
                $node->parentNode->removeChild($node);
            }
        }
    }
    return $dom->saveHTML();
}

function persistDataImages($html)
{
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    $imgs = $dom->getElementsByTagName('img');
    $baseDir = dirname(__DIR__, 4) . '/public/uploads/' . date('Y/m');
    $baseUrl = '/uploads/' . date('Y/m');

    if (!is_dir($baseDir)) {
        mkdir($baseDir, 0775, true);
    }

    foreach ($imgs as $img) {
        $src = $img->getAttribute('src');
        if (strpos($src, 'data:image/') !== 0) {
            continue;
        }
        if (!preg_match('#^data:(image/[\w.+-]+);base64,(.+)$#', $src, $m)) {
            continue;
        }
        $mime = $m[1];
        $data = base64_decode($m[2]);
        if ($data === false) {
            continue;
        }
        $ext = match ($mime) {
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'img'
        };
        $name = uniqid('img_', true) . '.' . $ext;
        $path = $baseDir . '/' . $name;
        file_put_contents($path, $data);
        @chmod($path, 0644);
        $img->setAttribute('src', $baseUrl . '/' . $name);
        if (!$img->getAttribute('alt')) {
            $img->setAttribute('alt', 'image');
        }
    }

    return $dom->saveHTML();
}

function savePage(PDO $pdo, $input)
{
    $id = isset($input['id']) ? (int)$input['id'] : null;
    $title = trim($input['title'] ?? '');
    $slug = trim($input['slug'] ?? '');
    $status = trim($input['status'] ?? 'draft');
    $html = $input['html_content'] ?? '';

    if (!$title) {
        throw new RuntimeException('Title is required');
    }

    $allowedStatus = ['draft', 'published'];
    if (!in_array($status, $allowedStatus, true)) {
        $status = 'draft';
    }

    $existingId = null;
    if ($id) {
        $checkStmt = $pdo->prepare('SELECT id FROM pages WHERE id = ?');
        $checkStmt->execute([$id]);
        $existingId = $checkStmt->fetchColumn() ? $id : null;
    }

    $slugBase = $slug ?: slugify($title);
    $slugFinal = ensureUniqueSlug($pdo, $slugBase, $existingId ?: null);

    $now = date('Y-m-d H:i:s');

    if ($existingId) {
        $sql = "UPDATE pages SET title = ?, slug = ?, status = ?, html_content = ?, updated_at = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$title, $slugFinal, $status, $html, $now, $existingId]);
        return $existingId;
    }

    $sql = "INSERT INTO pages (user_id, title, slug, status, html_content, created_at, updated_at) VALUES (?,?,?,?,?,?,?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([(int)($input['user_id'] ?? 1), $title, $slugFinal, $status, $html, $now, $now]);
    return (int)$pdo->lastInsertId();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $wantsJson = isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    $raw = $_POST['html_content'] ?? '';
    $clean = sanitizeHtml($raw);
    $withFiles = persistDataImages($clean);

    $payload = $_POST;
    $payload['html_content'] = $withFiles;

    try {
        $pageId = savePage($pdo, $payload);
        if ($wantsJson || $isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => true, 'page_id' => $pageId]);
        } else {
            header('Location: ?r=admin/pages');
        }
        exit;
    } catch (Throwable $e) {
        logErrorMessage($e->getMessage());
        if ($wantsJson || $isAjax) {
            http_response_code(422);
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
            exit;
        }
        $error = $e->getMessage();
    }
}

$orderType = $template['order_type'] ?? 'none';
$orderTypeLabel = 'Informasi saja';
$badgeClass = 'bg-secondary';
if ($orderType === 'link') {
    $orderTypeLabel = 'CTA Link';
    $badgeClass = 'bg-success';
} elseif ($orderType === 'gateway') {
    $orderTypeLabel = 'Payment Gateway';
    $badgeClass = 'bg-warning text-dark';
}
$ctaLabelValue = isset($old['cta_label']) && $old['cta_label'] !== '' ? $old['cta_label'] : 'Pesan Sekarang';
$ctaUrlValue = $old['cta_url'] ?? '';
$productNameValue = $old['product_name'] ?? '';
$productPriceValue = $old['product_price'] ?? '';
$productNoteValue = $old['product_note'] ?? '';
$statusValue = $old['status'] ?? 'draft';
?>
<div class="mb-4">
    <h1 class="h3">Create Landing Page</h1>
</div>
<div class="mb-3">
    <div class="alert alert-light border">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <strong>Template:</strong> <?php echo htmlspecialchars($template['name']); ?>
            </div>
            <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($orderTypeLabel); ?></span>
        </div>
        <?php if (!empty($template['description'])): ?>
            <p class="mb-0 text-muted small"><?php echo htmlspecialchars($template['description']); ?></p>
        <?php endif; ?>
    </div>
</div>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<form id="pageForm" method="POST" action="?r=admin/pages/store">
    <input type="hidden" name="_csrf" value="<?php echo htmlspecialchars(Csrf::token()); ?>">
    <input type="hidden" name="template_id" value="<?php echo htmlspecialchars($template['id']); ?>">
    <input type="hidden" name="order_type" value="<?php echo htmlspecialchars($orderType); ?>">
    <input type="hidden" name="id" value="<?php echo htmlspecialchars($old['id'] ?? ''); ?>">
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label class="form-label" for="title">Title</label>
            <input class="form-control" type="text" name="title" id="title" value="<?php echo htmlspecialchars($old['title'] ?? ''); ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="slug">Slug (optional)</label>
            <input class="form-control" type="text" name="slug" id="slug" value="<?php echo htmlspecialchars($old['slug'] ?? ''); ?>" placeholder="auto-fill from title if empty">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="status">Status</label>
            <select class="form-select" name="status" id="status">
                <option value="draft" <?php echo $statusValue === 'draft' ? 'selected' : ''; ?>>Draft</option>
                <!-- <option value="published" <?php echo $statusValue === 'published' ? 'selected' : ''; ?>>Published</option> -->
            </select>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Design Canvas</label>
         <div id="gjs" class="card border rounded-3 p-2" style="overflow: hidden; min-height: 400px; background-color: #fff;">
            <?php if ($baseHtml !== ''): ?>
            <?php echo $baseHtml; ?>
            <?php else: ?>
                <section class="section">
                    <h1>Headline</h1>
                    <p>Start building your landing page...</p>
                </section>
            <?php endif; ?>
        </div>
    </div>
    <div class="mb-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="h6 mb-0">Konfigurasi Order</h5>
                    <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($orderTypeLabel); ?></span>
                </div>
                <?php if ($orderType === 'link'): ?>
                    <p class="text-muted small mb-3">
                        Template ini menggunakan satu tombol utama, jadi isi CTA agar pengguna diarahkan ke WhatsApp, marketplace, atau halaman lain. Untuk landing satu tombol, lebih disarankan menggunakan <strong>CTA URL</strong>.
                    </p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="cta_label">CTA Label</label>
                            <input class="form-control" type="text" name="cta_label" id="cta_label" value="<?php echo htmlspecialchars($ctaLabelValue); ?>" placeholder="Pesan Sekarang">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="cta_url">CTA URL</label>
                            <input class="form-control" type="url" name="cta_url" id="cta_url" value="<?php echo htmlspecialchars($ctaUrlValue); ?>" placeholder="link tujuan tombol utama">
                            <div class="form-text">CTA URL akan disimpan sebagai tombol utama. Marketplace/social link lainnya tetap bisa diisi di bawah.</div>
                        </div>
                    </div>
                <?php elseif ($orderType === 'gateway'): ?>
                    <p class="text-muted small mb-3">
                        Template ini akan menampilkan elemen pembayaran. Isi konfigurasi produk supaya data tersimpan ke kolom <code>product_config</code> (nanti dipakai saat integrasi Midtrans).
                    </p>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="product_name">Nama Produk</label>
                            <input class="form-control" type="text" name="product_name" id="product_name" value="<?php echo htmlspecialchars($productNameValue); ?>" placeholder="Nama paket / produk">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="product_price">Harga Produk</label>
                            <input class="form-control" type="number" step="any" name="product_price" id="product_price" value="<?php echo htmlspecialchars($productPriceValue); ?>" placeholder="100000">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="product_note">Catatan Singkat</label>
                            <textarea class="form-control" name="product_note" id="product_note" rows="1" placeholder="Misal: Cukup bayar DP 50%"><?php echo htmlspecialchars($productNoteValue); ?></textarea>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-muted small mb-0">Tidak ada konfigurasi tambahan untuk template ini. Gunakan marketplace/social link jika perlu.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="mb-3">
        <h5 class="h6 mb-2">Marketplace & Social Links</h5>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="shopee_link">Shopee Link</label>
                <input class="form-control" type="url" name="shopee_link" id="shopee_link" value="<?php echo htmlspecialchars($old['shopee_link'] ?? ''); ?>" placeholder="https://shopee.co.id/...">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="tokped_link">Tokopedia Link</label>
                <input class="form-control" type="url" name="tokped_link" id="tokped_link" value="<?php echo htmlspecialchars($old['tokped_link'] ?? ''); ?>" placeholder="https://tokopedia.com/...">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="fb_link">Facebook Link</label>
                <input class="form-control" type="url" name="fb_link" id="fb_link" value="<?php echo htmlspecialchars($old['fb_link'] ?? ''); ?>" placeholder="https://facebook.com/...">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="ig_link">Instagram Link</label>
                <input class="form-control" type="url" name="ig_link" id="ig_link" value="<?php echo htmlspecialchars($old['ig_link'] ?? ''); ?>" placeholder="https://instagram.com/...">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="tiktok_link">TikTok Link</label>
                <input class="form-control" type="url" name="tiktok_link" id="tiktok_link" value="<?php echo htmlspecialchars($old['tiktok_link'] ?? ''); ?>" placeholder="https://tiktok.com/...">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="x_link">X Link</label>
                <input class="form-control" type="url" name="x_link" id="x_link" value="<?php echo htmlspecialchars($old['x_link'] ?? ''); ?>" placeholder="https://x.com/...">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="corporate">Corporate Link</label>
                <input class="form-control" type="url" name="corporate" id="corporate" value="<?php echo htmlspecialchars($old['corporate'] ?? ''); ?>" placeholder="https://corporate.example.com/...">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="publisher">Publisher Link</label>
                <input class="form-control" type="url" name="publisher" id="publisher" value="<?php echo htmlspecialchars($old['publisher'] ?? ''); ?>" placeholder="https://publisher.example.com/...">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="whatsapp">WhatsApp Link</label>
                <input class="form-control" type="url" name="whatsapp" id="whatsapp" value="<?php echo htmlspecialchars($old['whatsapp'] ?? ''); ?>" placeholder="https://wa.me/1234567890">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="telegram">Telegram Link</label>
                <input class="form-control" type="url" name="telegram" id="telegram" value="<?php echo htmlspecialchars($old['telegram'] ?? ''); ?>" placeholder="https://t.me/username">
            </div>            
        </div>
    </div>
    <textarea name="html_content" id="html_content" hidden></textarea>
    <button type="submit" class="btn btn-primary">Simpan Draft</button>
</form>
<script>
    const canvasEl = document.getElementById('gjs');
    const htmlField = document.getElementById('html_content');
    const formEl = document.getElementById('pageForm');
    let editor = null;

    if (typeof canvaseditor !== 'undefined' && canvasEl) {
        editor = canvaseditor.init({
            container: '#gjs',
            height: '90vh',
            fromElement: true
        });
    } else if (canvasEl) {
        canvasEl.setAttribute('contenteditable', 'true');
    }

    if (formEl) {
        formEl.addEventListener('submit', function () {
            let handled = false;
            if (window.LPB && typeof window.LPB.serialize === 'function') {
                const serialized = window.LPB.serialize();
                if (htmlField && serialized) {
                    htmlField.value = serialized;
                    handled = true;
                }
            }
            if (!handled) {
                if (editor && htmlField) {
                    htmlField.value = editor.getHtml();
                } else if (canvasEl && htmlField) {
                    htmlField.value = canvasEl.innerHTML;
                }
            }
        });
    }
</script>
<script>
  (function(){
    const form = document.getElementById('pageForm');
    form?.addEventListener('submit', function(){
      if (window.LPB?.serialize) window.LPB.serialize();
    });
  })();
</script>
