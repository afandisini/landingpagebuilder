<?php
$orderType = $page['order_type'] ?? 'none';
$orderTypeLabel = 'Informasi saja';
$badgeClass = 'bg-secondary';
if ($orderType === 'link') {
    $orderTypeLabel = 'CTA Link';
    $badgeClass = 'bg-success';
} elseif ($orderType === 'gateway') {
    $orderTypeLabel = 'Payment Gateway';
    $badgeClass = 'bg-warning text-dark';
}
$productConfig = json_decode($page['product_config'] ?? '', true) ?: [];
$ctaLabelValue = $page['cta_label'] ?? 'Pesan Sekarang';
$ctaUrlValue = $page['cta_url'] ?? '';
$productNameValue = $productConfig['name'] ?? '';
$productPriceValue = $productConfig['price'] ?? '';
$productNoteValue = $productConfig['note'] ?? '';
?>
<div class="mb-4">
    <h1 class="h3">Edit Landing Page</h1>
</div>
<form id="pageForm" method="post" action="?r=admin/pages/update">
    <input type="hidden" name="id" value="<?php echo htmlspecialchars($page['id']); ?>">
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label class="form-label" for="title">Title</label>
            <input class="form-control" type="text" name="title" id="title" value="<?php echo htmlspecialchars($page['title']); ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="slug">Slug</label>
            <input class="form-control" type="text" name="slug" id="slug" value="<?php echo htmlspecialchars($page['slug']); ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="template_id">Template</label>
            <select class="form-select" name="template_id" id="template_id">
                <?php foreach ($templates as $template): ?>
                    <option value="<?php echo htmlspecialchars($template['id']); ?>" <?php echo (int)$page['template_id'] === (int)$template['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($template['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="form-text">Ganti template jika ingin memulai layout baru.</div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="order_type">Order Type</label>
            <select class="form-select" name="order_type" id="order_type">
                <option value="none" <?php echo $orderType === 'none' ? 'selected' : ''; ?>>Informasi saja</option>
                <option value="link" <?php echo $orderType === 'link' ? 'selected' : ''; ?>>CTA Link</option>
                <option value="gateway" <?php echo $orderType === 'gateway' ? 'selected' : ''; ?>>Payment Gateway</option>
            </select>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">Design Canvas</label>
        <div id="gjs" style="border: 1px solid #ddd; min-height: 70vh;">
            <?php echo $page['html_content'] ?? '<section><h1>Edit your layout</h1></section>'; ?>
        </div>
    </div>

    <div class="mb-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="h6 mb-0">Konfigurasi Order</h5>
                    <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($orderTypeLabel); ?></span>
                </div>
                <div id="order-link-panel" class="<?php echo $orderType === 'link' ? '' : 'd-none'; ?>">
                    <p class="text-muted small mb-3">Isi CTA utama jika tipe order adalah link.</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="cta_label">CTA Label</label>
                            <input class="form-control" type="text" name="cta_label" id="cta_label" value="<?php echo htmlspecialchars($ctaLabelValue); ?>" placeholder="Pesan Sekarang">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="cta_url">CTA URL</label>
                            <input class="form-control" type="url" name="cta_url" id="cta_url" value="<?php echo htmlspecialchars($ctaUrlValue); ?>" placeholder="https://wa.me/..., marketplace, dll">
                        </div>
                    </div>
                </div>
                <div id="order-gateway-panel" class="<?php echo $orderType === 'gateway' ? '' : 'd-none'; ?>">
                    <p class="text-muted small mb-3">Konfigurasi produk untuk tipe Payment Gateway.</p>
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
                </div>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <h5 class="h6 mb-2">Marketplace & Social Links</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="shopee_link">Shopee Link</label>
                <input class="form-control" type="url" name="shopee_link" id="shopee_link" value="<?php echo htmlspecialchars($page['shopee_link'] ?? ''); ?>" placeholder="https://shopee.co.id/...">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="tokped_link">Tokopedia Link</label>
                <input class="form-control" type="url" name="tokped_link" id="tokped_link" value="<?php echo htmlspecialchars($page['tokped_link'] ?? ''); ?>" placeholder="https://tokopedia.com/...">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="fb_link">Facebook Link</label>
                <input class="form-control" type="url" name="fb_link" id="fb_link" value="<?php echo htmlspecialchars($page['fb_link'] ?? ''); ?>" placeholder="https://facebook.com/...">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="ig_link">Instagram Link</label>
                <input class="form-control" type="url" name="ig_link" id="ig_link" value="<?php echo htmlspecialchars($page['ig_link'] ?? ''); ?>" placeholder="https://instagram.com/...">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="tiktok_link">TikTok Link</label>
                <input class="form-control" type="url" name="tiktok_link" id="tiktok_link" value="<?php echo htmlspecialchars($page['tiktok_link'] ?? ''); ?>" placeholder="https://tiktok.com/...">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="x_link">X Link</label>
                <input class="form-control" type="url" name="x_link" id="x_link" value="<?php echo htmlspecialchars($page['x_link'] ?? ''); ?>" placeholder="https://x.com/...">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="corporate">Corporate Link</label>
                <input class="form-control" type="url" name="corporate" id="corporate" value="<?php echo htmlspecialchars($page['corporate'] ?? ''); ?>" placeholder="https://corporate.example.com/...">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="publisher">Publisher Link</label>
                <input class="form-control" type="url" name="publisher" id="publisher" value="<?php echo htmlspecialchars($page['publisher'] ?? ''); ?>" placeholder="https://publisher.example.com/...">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="whatsapp">WhatsApp Link</label>
                <input class="form-control" type="url" name="whatsapp" id="whatsapp" value="<?php echo htmlspecialchars($page['whatsapp'] ?? ''); ?>" placeholder="https://wa.me/1234567890">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="telegram">Telegram Link</label>
                <input class="form-control" type="url" name="telegram" id="telegram" value="<?php echo htmlspecialchars($page['telegram'] ?? ''); ?>" placeholder="https://t.me/username">
            </div>
        </div>
    </div>
    <textarea name="html_content" id="html_content" hidden></textarea>
    <button type="submit" class="btn btn-primary">Update Page</button>
</form>
<link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/vendor/canvaseditor/grapes.min.css">
<script src="<?php echo $baseUrl; ?>/assets/vendor/canvaseditor/grapes.min.js"></script>
<script>
    const canvasEl = document.getElementById('gjs');
    const htmlField = document.getElementById('html_content');
    const formEl = document.getElementById('pageForm');
    const orderTypeSelect = document.getElementById('order_type');
    const linkPanel = document.getElementById('order-link-panel');
    const gatewayPanel = document.getElementById('order-gateway-panel');

    let editor = null;
    if (typeof canvaseditor !== 'undefined') {
        editor = canvaseditor.init({
            container: '#gjs',
            height: '70vh',
            fromElement: true
        });
    } else {
        canvasEl.setAttribute('contenteditable', 'true');
    }

    if (orderTypeSelect) {
        orderTypeSelect.addEventListener('change', function () {
            const val = this.value;
            linkPanel.classList.toggle('d-none', val !== 'link');
            gatewayPanel.classList.toggle('d-none', val !== 'gateway');
        });
    }

    formEl.addEventListener('submit', function () {
        if (editor) {
            htmlField.value = editor.getHtml();
        } else {
            htmlField.value = canvasEl.innerHTML;
        }
    });
</script>
