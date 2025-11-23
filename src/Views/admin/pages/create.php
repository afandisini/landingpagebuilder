<?php
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
<form id="pageForm" method="post" action="?r=admin/pages/store">
    <input type="hidden" name="_csrf" value="<?php echo htmlspecialchars(Csrf::token()); ?>">
    <input type="hidden" name="template_id" value="<?php echo htmlspecialchars($template['id']); ?>">
    <input type="hidden" name="order_type" value="<?php echo htmlspecialchars($orderType); ?>">
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label class="form-label" for="title">Title</label>
            <input class="form-control" type="text" name="title" id="title" value="<?php echo htmlspecialchars($old['title'] ?? ''); ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="slug">Slug</label>
            <input class="form-control" type="text" name="slug" id="slug" value="<?php echo htmlspecialchars($old['slug'] ?? ''); ?>" required>
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
    if (typeof canvaseditor !== 'undefined') {
        editor = canvaseditor.init({
            container: '#gjs',
            height: '90vh',
            fromElement: true
        });
    } else {
        canvasEl.setAttribute('contenteditable', 'true');
    }
    formEl.addEventListener('submit', function () {
        if (editor) {
            htmlField.value = editor.getHtml();
        } else {
            htmlField.value = canvasEl.innerHTML;
        }
    });
</script>
