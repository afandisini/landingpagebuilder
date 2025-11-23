<?php
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/../Core/Csrf.php';
require_once __DIR__ . '/../Models/Page.php';
require_once __DIR__ . '/../Models/Template.php';

class PageController
{
    public function index(): void
    {
        Auth::requireLogin();
        $pages = Page::all();
        $config = require __DIR__ . '/../config/config.php';
        $content = $this->renderView(__DIR__ . '/../Views/admin/pages/index.php', [
            'pages' => $pages,
            'baseUrl' => $config['base_url'],
        ]);
        require __DIR__ . '/../Views/layouts/admin.php';
    }

    public function chooseTemplate(): void
    {
        Auth::requireLogin();
        $templates = Template::all();
        $config = require __DIR__ . '/../config/config.php';
        $content = $this->renderView(__DIR__ . '/../Views/admin/pages/choose-template.php', [
            'templates' => $templates,
            'baseUrl' => $config['base_url'],
        ]);
        require __DIR__ . '/../Views/layouts/admin.php';
    }

    public function selectTemplate(): void
    {
        Auth::requireLogin();
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            header('Location: ?r=admin/pages/template');
            exit;
        }
        $templateId = (int)($_POST['template_id'] ?? 0);
        if ($templateId <= 0 || !Template::find($templateId)) {
            header('Location: ?r=admin/pages/template');
            exit;
        }
        header('Location: ?r=admin/pages/create&template_id=' . $templateId);
        exit;
    }

    public function create(array $context = []): void
    {
        Auth::requireLogin();
        $templateId = (int)($context['template_id'] ?? ($_GET['template_id'] ?? 0));
        if ($templateId <= 0) {
            header('Location: ?r=admin/pages/template');
            exit;
        }

        $template = Template::find($templateId);
        if (!$template) {
            header('Location: ?r=admin/pages/template');
            exit;
        }

        $baseHtml = $this->loadTemplateBaseHtml($template['base_file']);
        $config = require __DIR__ . '/../config/config.php';
        $defaultOld = array_merge(['title' => '', 'slug' => ''], $this->defaultFormValues());
        $old = $context['old'] ?? $defaultOld;
        $content = $this->renderView(__DIR__ . '/../Views/admin/pages/create.php', [
            'error' => $context['error'] ?? '',
            'baseUrl' => $config['base_url'],
            'old' => $old,
            'template' => $template,
            'baseHtml' => $baseHtml,
        ]);
        require __DIR__ . '/../Views/layouts/admin.php';
    }

    public function store(): void
    {
        Auth::requireLogin();
        try {
            if (!Csrf::validate($_POST['_csrf'] ?? null)) {
                header('Location: ?r=admin/pages');
                exit;
            }
            $templateId = (int)($_POST['template_id'] ?? 0);
            $template = Template::find($templateId);
            if ($templateId <= 0 || !$template) {
                header('Location: ?r=admin/pages/template');
                exit;
            }

            $title = trim($_POST['title'] ?? '');
            $slugInput = trim($_POST['slug'] ?? '');
            $status = trim($_POST['status'] ?? 'draft');
            $status = in_array($status, ['draft', 'published'], true) ? $status : 'draft';
            $htmlContent = $this->normalizeHtml($_POST['html_content'] ?? '');
            $linkValues = $this->sanitizeLinks([
                'shopee_link' => $_POST['shopee_link'] ?? null,
                'tokped_link' => $_POST['tokped_link'] ?? null,
                'fb_link' => $_POST['fb_link'] ?? null,
                'ig_link' => $_POST['ig_link'] ?? null,
                'tiktok_link' => $_POST['tiktok_link'] ?? null,
                'x_link' => $_POST['x_link'] ?? null,
                'corporate' => $_POST['corporate'] ?? null,
                'publisher' => $_POST['publisher'] ?? null,
                'whatsapp' => $_POST['whatsapp'] ?? null,
                'telegram' => $_POST['telegram'] ?? null,
            ]);
            $orderType = $_POST['order_type'] ?? ($template['order_type'] ?? 'none');
            $allowedOrderTypes = ['none', 'link', 'gateway'];
            if (!in_array($orderType, $allowedOrderTypes, true)) {
                $orderType = 'none';
            }
            $ctaLabel = trim($_POST['cta_label'] ?? '');
            $ctaUrl = trim($_POST['cta_url'] ?? '');
            $productName = trim($_POST['product_name'] ?? '');
            $productPrice = trim($_POST['product_price'] ?? '');
            $productNote = trim($_POST['product_note'] ?? '');
            $productConfig = null;
            if ($orderType === 'gateway') {
                $configPayload = [
                    'name' => $productName,
                    'price' => $productPrice,
                    'note' => $productNote,
                ];
                $encodedConfig = json_encode($configPayload, JSON_UNESCAPED_UNICODE);
                $productConfig = $encodedConfig === false ? null : $encodedConfig;
            }
            $oldInput = array_merge(
                ['title' => $title, 'slug' => $slugInput],
                array_map(static function ($val) {
                    return $val ?? '';
                }, $linkValues),
                [
                    'cta_label' => $ctaLabel,
                    'cta_url' => $ctaUrl,
                    'product_name' => $productName,
                    'product_price' => $productPrice,
                    'product_note' => $productNote,
                ]
            );

            if ($title === '') {
                $this->create([
                    'error' => 'Title is required.',
                    'template_id' => $templateId,
                    'old' => $oldInput,
                ]);
                return;
            }

            $slugBase = $this->slugify($slugInput ?: $title);
            $slugFinal = $this->ensureUniqueSlug($slugBase);

            $user = Auth::user();
            Page::create(array_merge([
                'user_id' => $user['id'],
                'title' => $title,
                'slug' => $slugFinal,
                'status' => $status,
                'html_content' => $htmlContent,
                'template_id' => $templateId,
                'order_type' => $orderType,
                'cta_label' => $ctaLabel,
                'cta_url' => $ctaUrl,
                'product_config' => $productConfig,
            ], $linkValues));

            header('Location: ?r=admin/pages');
            exit;
        } catch (\Throwable $e) {
            $this->logError('store: ' . $e->getMessage());
            $fallbackOld = [
                'title' => $_POST['title'] ?? '',
                'slug' => $_POST['slug'] ?? '',
            ];
            $this->create([
                'error' => 'Gagal menyimpan halaman: ' . $e->getMessage(),
                'template_id' => (int)($_POST['template_id'] ?? 0),
                'old' => $fallbackOld,
            ]);
        }
    }

    public function edit(): void
    {
        Auth::requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $page = Page::find($id);
        if (!$page) {
            echo 'Page not found';
            return;
        }
        $config = require __DIR__ . '/../config/config.php';
        $content = $this->renderView(__DIR__ . '/../Views/admin/pages/edit.php', [
            'page' => $page,
            'baseUrl' => $config['base_url'],
            'templates' => Template::all(),
        ]);
        require __DIR__ . '/../Views/layouts/admin.php';
    }

    public function update(): void
    {
        Auth::requireLogin();
        try {
            if (!Csrf::validate($_POST['_csrf'] ?? null)) {
                header('Location: ?r=admin/pages');
                exit;
            }
            $id = (int)($_POST['id'] ?? 0);
            $page = Page::find($id);
            if (!$page) {
                header('Location: ?r=admin/pages');
                exit;
            }

            $title = trim($_POST['title'] ?? '');
            $slugInput = trim($_POST['slug'] ?? '');
            $status = trim($_POST['status'] ?? 'draft');
            $status = in_array($status, ['draft', 'published'], true) ? $status : 'draft';
            $htmlContent = $this->normalizeHtml($_POST['html_content'] ?? '');
            $linkValues = $this->sanitizeLinks([
                'shopee_link' => $_POST['shopee_link'] ?? null,
                'tokped_link' => $_POST['tokped_link'] ?? null,
                'fb_link' => $_POST['fb_link'] ?? null,
                'ig_link' => $_POST['ig_link'] ?? null,
                'tiktok_link' => $_POST['tiktok_link'] ?? null,
                'x_link' => $_POST['x_link'] ?? null,
                'corporate' => $_POST['corporate'] ?? null,
                'publisher' => $_POST['publisher'] ?? null,
                'whatsapp' => $_POST['whatsapp'] ?? null,
                'telegram' => $_POST['telegram'] ?? null,
            ]);
            $templateId = (int)($_POST['template_id'] ?? ($page['template_id'] ?? 0));
            $template = $templateId > 0 ? Template::find($templateId) : null;
            if (!$template && !empty($page['template_id'])) {
                $templateId = (int)$page['template_id'];
            }
            $orderType = $_POST['order_type'] ?? ($page['order_type'] ?? 'none');
            $allowedOrderTypes = ['none', 'link', 'gateway'];
            if (!in_array($orderType, $allowedOrderTypes, true)) {
                $orderType = 'none';
            }
            $ctaLabel = trim($_POST['cta_label'] ?? '');
            $ctaUrl = trim($_POST['cta_url'] ?? '');
            $productName = trim($_POST['product_name'] ?? '');
            $productPrice = trim($_POST['product_price'] ?? '');
            $productNote = trim($_POST['product_note'] ?? '');
            $productConfig = null;
            if ($orderType === 'gateway') {
                $configPayload = [
                    'name' => $productName,
                    'price' => $productPrice,
                    'note' => $productNote,
                ];
                $encodedConfig = json_encode($configPayload, JSON_UNESCAPED_UNICODE);
                $productConfig = $encodedConfig === false ? null : $encodedConfig;
            }

            if ($id === 0 || $title === '') {
                header('Location: ?r=admin/pages');
                exit;
            }

            $slugBase = $this->slugify($slugInput ?: $title);
            $slugFinal = $this->ensureUniqueSlug($slugBase, $id);

            Page::update($id, array_merge([
                'title' => $title,
                'slug' => $slugFinal,
                'html_content' => $htmlContent,
                'status' => $status,
                'template_id' => $templateId,
                'order_type' => $orderType,
                'cta_label' => $ctaLabel,
                'cta_url' => $ctaUrl,
                'product_config' => $productConfig,
                'published_path' => $page['published_path'] ?? null,
                'published_at' => $page['published_at'] ?? null,
            ], $linkValues));

            header('Location: ?r=admin/pages');
            exit;
        } catch (\Throwable $e) {
            $this->logError('update: ' . $e->getMessage());
            header('Location: ?r=admin/pages');
            exit;
        }
    }

    public function delete(): void
    {
        Auth::requireLogin();
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            header('Location: ?r=admin/pages');
            exit;
        }
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            header('Location: ?r=admin/pages');
            exit;
        }
        $page = Page::find($id);
        if (!$page) {
            header('Location: ?r=admin/pages');
            exit;
        }

        Page::delete($id);

        // Remove generated static file if it exists and is within /public directory.
        if (!empty($page['published_path'])) {
            $publicRoot = realpath(__DIR__ . '/../../public');
            $targetPath = $publicRoot . '/' . ltrim($page['published_path'], '/\\');
            $realTarget = realpath($targetPath);
            if ($publicRoot && $realTarget && strpos($realTarget, $publicRoot) === 0 && is_file($realTarget)) {
                @unlink($realTarget);
            }
        }

        header('Location: ?r=admin/pages');
        exit;
    }

    public function publish(): void
    {
        Auth::requireLogin();
        try {
            if (!Csrf::validate($_POST['_csrf'] ?? null)) {
                header('Location: ?r=admin/pages');
                exit;
            }
            $id = (int)($_POST['id'] ?? 0);
            $page = Page::find($id);
            if (!$page || empty($page['slug'])) {
                header('Location: ?r=admin/pages');
                exit;
            }

            $config = require __DIR__ . '/../config/config.php';
            $slug = $page['slug'];
            $publicPath = __DIR__ . '/../../public/page';
            if (!is_dir($publicPath)) {
                mkdir($publicPath, 0755, true);
            }
            $targetFile = $publicPath . '/' . $slug . '.html';
            $trackingUrl = $config['base_url'] . '/tracker.php?page_id=' . $page['id'];
            $bootstrapCss = $config['base_url'] . '/assets/bootstrap/css/bootstrap.min.css';
            $bootstrapIcons = $config['base_url'] . '/assets/bootstrap-icons/bootstrap-icons.min.css';
            $bootstrapBundle = $config['base_url'] . '/assets/bootstrap/js/bootstrap.bundle.min.js';
            $html = $page['html_content'] ?? '';

            // Build social links from page fields so published pages always render buttons.
            $linkMap = [
                'shopee_link' => ['label' => 'Shopee', 'icon' => 'bi-bag'],
                'tokped_link' => ['label' => 'Tokopedia', 'icon' => 'bi-bag-check'],
                'fb_link' => ['label' => 'Facebook', 'icon' => 'bi-facebook'],
                'ig_link' => ['label' => 'Instagram', 'icon' => 'bi-instagram'],
                'tiktok_link' => ['label' => 'TikTok', 'icon' => 'bi-tiktok'],
                'x_link' => ['label' => 'X', 'icon' => 'bi-twitter'],
                'whatsapp' => ['label' => 'WhatsApp', 'icon' => 'bi-whatsapp'],
                'telegram' => ['label' => 'Telegram', 'icon' => 'bi-telegram'],
                'corporate' => ['label' => 'Corporate', 'icon' => 'bi-briefcase'],
                'publisher' => ['label' => 'Publisher', 'icon' => 'bi-link-45deg'],
            ];
            $socialButtons = [];
            foreach ($linkMap as $field => $meta) {
                $url = trim($page[$field] ?? '');
                if ($url === '') {
                    continue;
                }
                $label = $meta['label'];
                $icon = $meta['icon'];
                $socialButtons[] = '<a class="btn btn-outline-secondary btn-sm me-2 mb-2 social-btn social-' . htmlspecialchars($field) . '" href="' . htmlspecialchars($url) . '" target="_blank" rel="noopener noreferrer"><i class="bi ' . htmlspecialchars($icon) . ' me-1"></i>' . htmlspecialchars($label) . '</a>';
            }
            $socialHtml = '';
            if (!empty($socialButtons)) {
                $socialHtml = '<div class="social-links d-flex flex-wrap align-items-center gap-2 mt-3">' . implode('', $socialButtons) . '</div>';
            }

            // Replace placeholder with generated social buttons or append when missing.
            if ($socialHtml !== '' && strpos($html, '<!--SOCIAL_LINKS-->') !== false) {
                $html = str_replace('<!--SOCIAL_LINKS-->', $socialHtml, $html);
            } elseif ($socialHtml !== '') {
                $html .= "\n" . $socialHtml;
            } elseif (strpos($html, '<!--SOCIAL_LINKS-->') !== false) {
                // If no links, drop the entire section containing the placeholder so it won't render.
                $pattern = '/<section[^>]*>[^<]*?<!--SOCIAL_LINKS-->.*?<\\/section>/is';
                $removed = preg_replace($pattern, '', $html);
                if ($removed !== null) {
                    $html = $removed;
                } else {
                    // Fallback: just strip the placeholder comment.
                    $html = str_replace('<!--SOCIAL_LINKS-->', '', $html);
                }
            }

            $linkPayload = json_encode(array_filter([
                'shopee_link' => $page['shopee_link'] ?? null,
                'tokped_link' => $page['tokped_link'] ?? null,
                'fb_link' => $page['fb_link'] ?? null,
                'ig_link' => $page['ig_link'] ?? null,
                'tiktok_link' => $page['tiktok_link'] ?? null,
                'x_link' => $page['x_link'] ?? null,
                'whatsapp' => $page['whatsapp'] ?? null,
                'telegram' => $page['telegram'] ?? null,
                'corporate' => $page['corporate'] ?? null,
                'publisher' => $page['publisher'] ?? null,
                'cta_label' => $page['cta_label'] ?? null,
                'cta_url' => $page['cta_url'] ?? null,
            ], static function ($val) {
                return $val !== null && trim((string)$val) !== '';
            }), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
            if ($linkPayload === false) {
                $linkPayload = '{}';
            }
            $finalHtml = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($page['title']) . '</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="' . $bootstrapCss . '">
    <link rel="stylesheet" href="' . $bootstrapIcons . '">
</head>
<body>
<div class="container-fluid py-5">
    ' . $html . '
</div>
<script>
    (function() {
        var img = new Image();
        img.src = "' . $trackingUrl . '&ts=" + Date.now();
    })();
</script>
<script>
    window.landingPageLinks = ' . $linkPayload . ';
    window.pageId = ' . (int)$page['id'] . ';
</script>
<script src="' . $bootstrapBundle . '"></script>
</body>
</html>';

            file_put_contents($targetFile, $finalHtml);

            Page::update($id, [
                'title' => $page['title'],
                'slug' => $slug,
                'html_content' => $page['html_content'],
                'status' => 'published',
                'shopee_link' => $page['shopee_link'],
                'tokped_link' => $page['tokped_link'],
                'fb_link' => $page['fb_link'],
                'ig_link' => $page['ig_link'],
                'tiktok_link' => $page['tiktok_link'],
                'x_link' => $page['x_link'],
                'corporate' => $page['corporate'],
                'publisher' => $page['publisher'],
                'whatsapp' => $page['whatsapp'],
                'telegram' => $page['telegram'],
                'template_id' => $page['template_id'] ?? null,
                'order_type' => $page['order_type'] ?? 'none',
                'cta_label' => $page['cta_label'] ?? null,
                'cta_url' => $page['cta_url'] ?? null,
                'product_config' => $page['product_config'] ?? null,
                'published_path' => 'page/' . $slug . '.html',
                'published_at' => date('Y-m-d H:i:s'),
            ]);

            header('Location: ?r=admin/pages');
            exit;
        } catch (\Throwable $e) {
            $this->logError('publish: ' . $e->getMessage());
            header('Location: ?r=admin/pages');
            exit;
        }
    }

    private function renderView(string $view, array $params = []): string
    {
        extract($params, EXTR_SKIP);
        ob_start();
        include $view;
        return ob_get_clean();
    }

    // Trim link inputs and convert empty values to null so they stay optional.
    private function sanitizeLinks(array $links): array
    {
        $cleaned = [];
        foreach ($links as $key => $value) {
            $trimmed = trim((string)($value ?? ''));
            $cleaned[$key] = $trimmed === '' ? null : $trimmed;
        }
        return $cleaned;
    }

    // Provide baseline form values for social links, CTA, and payment inputs.
    private function defaultFormValues(): array
    {
        return array_merge(
            array_fill_keys([
                'shopee_link',
                'tokped_link',
                'fb_link',
                'ig_link',
                'tiktok_link',
                'x_link',
                'corporate',
                'publisher',
                'whatsapp',
                'telegram',
            ], ''),
            [
                'cta_label' => '',
                'cta_url' => '',
                'product_name' => '',
                'product_price' => '',
                'product_note' => '',
            ]
        );
    }

    // Load the default template markup so CanvasEditor starts with a base layout.
    private function loadTemplateBaseHtml(string $baseFile): string
    {
        $cleanFile = str_replace(['../', '..\\'], '', $baseFile);
        $path = __DIR__ . '/../../public/' . ltrim($cleanFile, '/\\');
        if (!is_file($path)) {
            return '';
        }
        $content = file_get_contents($path);
        return $content === false ? '' : $content;
    }

    private function logError(string $message): void
    {
        $logDir = __DIR__ . '/../../log';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }
        $logFile = $logDir . '/log_error.txt';
        $uri = $_SERVER['REQUEST_URI'] ?? 'cli';
        $line = date('Y-m-d H:i:s') . ' [' . $uri . '] ' . $message . PHP_EOL;
        file_put_contents($logFile, $line, FILE_APPEND);
    }

    private function normalizeHtml(string $html): string
    {
        $clean = $this->sanitizeHtml($html);
        return $this->persistDataImages($clean);
    }

    private function sanitizeHtml(string $html): string
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
            $classes = array_filter(array_map('trim', explode(' ', $m[1])), static fn($c) => !preg_match('/^lpb-/', $c));
            return count($classes) ? 'class="' . implode(' ', $classes) . '"' : '';
        }, $html);

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);
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

    private function persistDataImages(string $html): string
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $imgs = $dom->getElementsByTagName('img');
        $baseDir = __DIR__ . '/../../public/uploads/' . date('Y/m');
        $baseUrl = '/uploads/' . date('Y/m');

        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0775, true);
        }

        foreach ($imgs as $img) {
            $src = $img->getAttribute('src');
            if (strpos($src, 'data:image/') !== 0) {
                continue;
            }
            if (!preg_match('#^data:(image/[\\w.+-]+);base64,(.+)$#', $src, $m)) {
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

    private function slugify(string $text): string
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text ?: 'page');
        return $text === '' ? 'page' : $text;
    }

    private function ensureUniqueSlug(string $base, ?int $excludeId = null): string
    {
        $slug = $base;
        $i = 2;
        while (true) {
            $existing = Page::findBySlug($slug);
            if (!$existing || ($excludeId && (int)$existing['id'] === (int)$excludeId)) {
                return $slug;
            }
            $slug = $base . '-' . $i++;
        }
    }
}
