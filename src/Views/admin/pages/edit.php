<?php
date_default_timezone_set('Asia/Jakarta');

function logErrorMessage($message)
{
    $logDir = dirname(__DIR__, 3) . '/log';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0775, true);
    }
    $file = $logDir . '/log_error.txt';
    $line = date('Y-m-d H:i:s') . ' [' . ($_SERVER['REQUEST_URI'] ?? 'cli') . '] ' . $message . PHP_EOL;
    file_put_contents($file, $line, FILE_APPEND);
}

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
$statusValue = $page['status'] ?? 'draft';
$codeMirrorBase = htmlspecialchars(rtrim($baseUrl, '/') . '/assets/codemirror');
?>
<link rel="stylesheet" href="<?php echo $codeMirrorBase; ?>/lib/codemirror.css">
<link rel="stylesheet" href="<?php echo $codeMirrorBase; ?>/theme/material-darker.css">
<style>
    .CodeMirror {
        min-height: 420px;
        border: 1px solid #ced4da;
        border-radius: 0.75rem;
        box-shadow: 0 12px 28px rgba(0, 0, 0, 0.08);
        font-size: 0.95rem;
    }
    .CodeMirror-scroll { min-height: 400px; }
</style>
<div class="mb-4">
    <h1 class="h3">Edit Landing Page</h1>
</div>
<form id="pageForm" method="post" action="?r=admin/pages/update">
    <input type="hidden" name="id" value="<?php echo htmlspecialchars($page['id']); ?>">
    <input type="hidden" name="_csrf" value="<?php echo htmlspecialchars(Csrf::token()); ?>">
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label class="form-label" for="title">Title</label>
            <input class="form-control" type="text" name="title" id="title" value="<?php echo htmlspecialchars($page['title']); ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="slug">Slug</label>
            <input class="form-control" type="text" name="slug" id="slug" value="<?php echo htmlspecialchars($page['slug']); ?>" placeholder="auto-fill from title if empty">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="status">Status</label>
            <select class="form-select" name="status" id="status">
                <option value="draft" <?php echo $statusValue === 'draft' ? 'selected' : ''; ?>>Draft</option>
                <!-- <option value="published" <?php echo $statusValue === 'published' ? 'selected' : ''; ?>>Published</option> -->
            </select>
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
        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="form-label d-flex align-items-center gap-2 mb-0">
                Design Canvas
                <span class="badge bg-primary">Edit ID #<?php echo htmlspecialchars($page['id']); ?></span>
            </label>
            <div class="btn-group rounded-3" role="group" aria-label="Canvas View Toggle" data-builder-only="true">
                <button type="button" class="btn btn-secondary btn-sm" id="edit-html-btn">
                    <i class="bi bi-code-slash w-50 me-1"></i>Edit Html
                </button>
                <button type="button" class="btn btn-warning btn-sm active" id="view-btn">
                    <i class="bi bi-eye w-50 me-1"></i>Visual
                </button>
            </div>
        </div>
        <div id="gjs" class="card border rounded-3 p-2" style="overflow: hidden; min-height: 400px; background-color: #fff;">
            <?php echo $page['html_content'] ?? '<section><h1>Edit your layout</h1></section>'; ?>
        </div>
        <textarea id="html-raw-editor" class="form-control font-monospace d-none mt-2" rows="16" spellcheck="false" style="min-height: 400px;"></textarea>
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
<script src="<?php echo $codeMirrorBase; ?>/lib/codemirror.js"></script>
<script src="<?php echo $codeMirrorBase; ?>/mode/xml/xml.js"></script>
<script src="<?php echo $codeMirrorBase; ?>/mode/javascript/javascript.js"></script>
<script src="<?php echo $codeMirrorBase; ?>/mode/css/css.js"></script>
<script src="<?php echo $codeMirrorBase; ?>/mode/htmlmixed/htmlmixed.js"></script>
<script src="<?php echo $codeMirrorBase; ?>/addon/fold/xml-fold.js"></script>
<script src="<?php echo $codeMirrorBase; ?>/addon/edit/closetag.js"></script>
<script src="<?php echo $codeMirrorBase; ?>/addon/edit/closebrackets.js"></script>
<script src="<?php echo $codeMirrorBase; ?>/addon/edit/matchbrackets.js"></script>
<script src="<?php echo $codeMirrorBase; ?>/addon/edit/matchtags.js"></script>
<script src="<?php echo $codeMirrorBase; ?>/addon/selection/active-line.js"></script>
<script>
(function () {
    const canvasEl = document.getElementById('gjs');
    const htmlField = document.getElementById('html_content');
    const htmlRawEditor = document.getElementById('html-raw-editor');
    const formEl = document.getElementById('pageForm');
    const orderTypeSelect = document.getElementById('order_type');
    const linkPanel = document.getElementById('order-link-panel');
    const gatewayPanel = document.getElementById('order-gateway-panel');
    const editHtmlBtn = document.getElementById('edit-html-btn');
    const viewBtn = document.getElementById('view-btn');
    let currentMode = 'visual';
    let visualEditor = null;
    let codeEditor = null;

    (function resolveVisualEditor() {
        if (window.gjsEditor) {
            visualEditor = window.gjsEditor;
            return;
        }
        if (window.editor && typeof window.editor.getHtml === 'function') {
            visualEditor = window.editor;
            window.gjsEditor = visualEditor;
            return;
        }
        if (typeof window.canvaseditor !== 'undefined' && canvasEl) {
            visualEditor = window.canvaseditor.init({
                container: '#gjs',
                height: '90vh',
                fromElement: true,
                storageManager: false
            });
            window.gjsEditor = visualEditor;
            return;
        }
        if (canvasEl) {
            canvasEl.setAttribute('contenteditable', 'true');
        }
    })();

    try {
        if (visualEditor?.StorageManager) {
            visualEditor.StorageManager.disable?.();
            visualEditor.StorageManager.setAutosave?.(false);
        }
    } catch (e) {
        console.warn('Storage manager disabled fallback', e);
    }

    window.addEventListener('unhandledrejection', function (evt) {
        const reason = evt.reason;
        const msg = typeof reason === 'string' ? reason : (reason?.message || '');
        if (msg && msg.toLowerCase().includes('storage is not allowed')) {
            evt.preventDefault();
            console.warn('Suppressed storage access error:', msg);
        }
    });

    if (orderTypeSelect) {
        orderTypeSelect.addEventListener('change', function () {
            const val = this.value;
            linkPanel?.classList.toggle('d-none', val !== 'link');
            gatewayPanel?.classList.toggle('d-none', val !== 'gateway');
        });
    }

    function ensureCodeEditor() {
        if (codeEditor || typeof CodeMirror === 'undefined' || !htmlRawEditor) {
            return codeEditor;
        }
        codeEditor = CodeMirror.fromTextArea(htmlRawEditor, {
            mode: 'htmlmixed',
            theme: 'material-darker',
            lineNumbers: true,
            lineWrapping: true,
            autoCloseTags: true,
            autoCloseBrackets: true,
            matchBrackets: true,
            matchTags: { bothTags: true },
            styleActiveLine: true,
            viewportMargin: Infinity
        });
        codeEditor.setSize('100%', 520);
        const wrapper = codeEditor.getWrapperElement();
        wrapper.classList.add('mt-2', 'd-none');
        return codeEditor;
    }

    function showCodeEditor() {
        const cm = ensureCodeEditor();
        if (!cm) {
            htmlRawEditor?.classList.remove('d-none');
            return null;
        }
        cm.getWrapperElement().classList.remove('d-none');
        cm.refresh();
        return cm;
    }

    function hideCodeEditor() {
        if (codeEditor) {
            codeEditor.getWrapperElement().classList.add('d-none');
        }
        htmlRawEditor?.classList.add('d-none');
    }

    function getVisualHtml() {
        try {
            if (visualEditor && typeof visualEditor.getHtml === 'function') {
                return visualEditor.getHtml();
            }
        } catch (e) {
            console.warn('getHtml failed, fallback to DOM', e);
        }
        try {
            if (window.LPB && typeof window.LPB.serialize === 'function') {
                return window.LPB.serialize();
            }
        } catch (e) {
            console.warn('LPB serialize failed', e);
        }
        return canvasEl ? canvasEl.innerHTML : '';
    }

    function applyHtmlToCanvas(html) {
        if (visualEditor && typeof visualEditor.setComponents === 'function') {
            try { visualEditor.setComponents(html); return true; } catch (e) {}
        }
        if (window.LPB && typeof window.LPB.setHtml === 'function') {
            try { window.LPB.setHtml(html); return true; } catch (e) {}
        }
        if (canvasEl) {
            canvasEl.innerHTML = html;
            return true;
        }
        return false;
    }

    function enterCodeMode() {
        const cm = showCodeEditor();
        const html = getVisualHtml();
        if (cm) {
            cm.setValue(html || '');
            cm.focus();
            setTimeout(() => cm.refresh(), 30);
        } else if (htmlRawEditor) {
            htmlRawEditor.value = html || '';
        }
        canvasEl?.classList.add('d-none');
        editHtmlBtn?.classList.add('active');
        viewBtn?.classList.remove('active');
        currentMode = 'code';
    }

    function enterVisualMode(applyChanges = true) {
        if (currentMode === 'code' && applyChanges) {
            const html = codeEditor ? codeEditor.getValue() : (htmlRawEditor?.value || '');
            applyHtmlToCanvas(html);
        }
        hideCodeEditor();
        canvasEl?.classList.remove('d-none');
        viewBtn?.classList.add('active');
        editHtmlBtn?.classList.remove('active');
        currentMode = 'visual';
    }

    editHtmlBtn?.addEventListener('click', () => enterCodeMode());
    viewBtn?.addEventListener('click', () => enterVisualMode(true));

    document.addEventListener('keydown', function (evt) {
        if (currentMode !== 'code') return;
        const isSave = (evt.ctrlKey || evt.metaKey) && evt.key.toLowerCase() === 's';
        if (isSave) {
            evt.preventDefault();
            const html = codeEditor ? codeEditor.getValue() : (htmlRawEditor?.value || '');
            applyHtmlToCanvas(html);
            return;
        }
        if (evt.key === 'Escape') {
            evt.preventDefault();
            enterVisualMode(false);
        }
    });

    if (formEl) {
        formEl.addEventListener('submit', function () {
            if (!htmlField) return;
            if (currentMode === 'code') {
                htmlField.value = codeEditor ? codeEditor.getValue() : (htmlRawEditor?.value || '');
                return;
            }
            htmlField.value = getVisualHtml();
        });
    }
})();
</script>
<script>
  (function(){
    const form = document.getElementById('pageForm');
    form?.addEventListener('submit', function(){
      if (window.LPB?.serialize) window.LPB.serialize();
    });
  })();
</script>
