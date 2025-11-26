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
            $config = require __DIR__ . '/../config/config.php';
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
            $slugInput = trim($_POST['slug'] ?? '') ?: 'demo-lp-01';
            $status = 'published';
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
            $pageId = Page::create(array_merge([
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

            $pagePayload = array_merge([
                'id' => $pageId,
                'title' => $title,
                'slug' => $slugFinal,
                'status' => $status,
                'html_content' => $htmlContent,
                'template_id' => $templateId,
                'order_type' => $orderType,
                'cta_label' => $ctaLabel,
                'cta_url' => $ctaUrl,
                'product_config' => $productConfig,
            ], $linkValues);

            $published = $this->generateStaticPage($pagePayload, $config, 'demo-lp-01');

            Page::update($pageId, array_merge([
                'title' => $title,
                'slug' => $slugFinal,
                'html_content' => $htmlContent,
                'status' => 'published',
                'template_id' => $templateId,
                'order_type' => $orderType,
                'cta_label' => $ctaLabel,
                'cta_url' => $ctaUrl,
                'product_config' => $productConfig,
                'published_path' => $published['published_path'],
                'published_at' => $published['published_at'],
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
            $published = $this->generateStaticPage($page, $config);

            Page::update($id, [
                'title' => $page['title'],
                'slug' => $page['slug'],
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
                'published_path' => $published['published_path'],
                'published_at' => $published['published_at'],
            ]);

            header('Location: ?r=admin/pages');
            exit;
        } catch (\Throwable $e) {
            $this->logError('publish: ' . $e->getMessage());
            header('Location: ?r=admin/pages');
            exit;
        }
    }

    private function generateStaticPage(array $page, array $config, ?string $slugOverride = null): array
    {
        $rawSlug = $slugOverride ?: ($page['slug'] ?? 'page');
        $slug = $this->slugify($rawSlug);

        $publicPath = __DIR__ . '/../../public/page';
        if (!is_dir($publicPath)) {
            mkdir($publicPath, 0755, true);
        }
        $targetFile = $publicPath . '/' . $slug . '.html';
        $trackingUrl = $config['base_url'] . '/tracker.php?page_id=' . $page['id'];
        $favicon = $config['base_url'] . '/favicon.ico';
        $bootstrapCss = $config['base_url'] . '/assets/bootstrap/css/bootstrap.min.css';
        $bootstrapIcons = $config['base_url'] . '/assets/bootstrap-icons/bootstrap-icons.min.css';
        $bootstrapBundle = $config['base_url'] . '/assets/bootstrap/js/bootstrap.bundle.min.js';
        $html = $this->stripUploadUi($page['html_content'] ?? '');

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

        if ($socialHtml !== '' && strpos($html, '<!--SOCIAL_LINKS-->') !== false) {
            $html = str_replace('<!--SOCIAL_LINKS-->', $socialHtml, $html);
        } elseif ($socialHtml !== '') {
            $html .= "\n" . $socialHtml;
        } elseif (strpos($html, '<!--SOCIAL_LINKS-->') !== false) {
            $pattern = '/<section[^>]*>[^<]*?<!--SOCIAL_LINKS-->.*?<\\/section>/is';
            $removed = preg_replace($pattern, '', $html);
            if ($removed !== null) {
                $html = $removed;
            } else {
                $html = str_replace('<!--SOCIAL_LINKS-->', '', $html);
            }
        }

        $ctaLabel = trim($page['cta_label'] ?? '');
        $ctaUrl = trim($page['cta_url'] ?? '');
        if ($ctaUrl !== '') {
            $ctaLabel = $ctaLabel !== '' ? $ctaLabel : 'Pesan Sekarang';
            $ctaMarkup = '<div class="cta-primary text-center my-3" data-cta="primary"><a class="btn btn-primary btn-lg px-4" href="' . htmlspecialchars($ctaUrl) . '" target="_blank" rel="noopener noreferrer">' . htmlspecialchars($ctaLabel) . '</a></div>';
            $ctaInserted = false;

            if (strpos($html, '<!--CTA_BUTTON-->') !== false) {
                $html = str_replace('<!--CTA_BUTTON-->', $ctaMarkup, $html);
                $ctaInserted = true;
            }

            if (!$ctaInserted && strpos($html, '<!--SOCIAL_LINKS-->') !== false) {
                $html = str_replace('<!--SOCIAL_LINKS-->', $ctaMarkup . '<!--SOCIAL_LINKS-->', $html);
                $ctaInserted = true;
            }

            if (!$ctaInserted && $socialHtml !== '' && strpos($html, $socialHtml) !== false) {
                $pos = strpos($html, $socialHtml);
                if ($pos !== false) {
                    $html = substr($html, 0, $pos) . $ctaMarkup . $socialHtml . substr($html, $pos + strlen($socialHtml));
                    $ctaInserted = true;
                }
            }

            if (!$ctaInserted) {
                $html = $ctaMarkup . $html;
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
    <link rel="icon" href="' . $favicon . '" type="image/x-icon">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="' . $bootstrapCss . '">
    <link rel="stylesheet" href="' . $bootstrapIcons . '">
</head>
<body>
<div class="container py-5">
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

        return [
            'slug' => $slug,
            'published_path' => 'page/' . $slug . '.html',
            'published_at' => date('Y-m-d H:i:s'),
        ];
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
        // strip stray XML declarations that sometimes get injected by DOMDocument
        $html = preg_replace('/<\?xml[^>]*\?>/i', '', $html);
        $html = preg_replace('/<!--\?xml[^>]*\?-->/i', '', $html);
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
        // remove any script tags left by builder UI
        foreach ($xpath->query('//script') as $scriptNode) {
            $scriptNode->parentNode?->removeChild($scriptNode);
        }
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
        $this->stripUploadUiFromDom($dom);
        return $dom->saveHTML();
    }

    private function stripUploadUi(string $html): string
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $this->stripUploadUiFromDom($dom);
        return $dom->saveHTML();
    }

    private function stripUploadUiFromDom(\DOMDocument $dom): void
    {
        $xpath = new \DOMXPath($dom);

        $styleNodes = [];
        foreach ($xpath->query('//style') as $style) {
            $styleNodes[] = $style;
        }
        foreach ($styleNodes as $style) {
            if ($style->parentNode) {
                $style->parentNode->removeChild($style);
            }
        }

        $uploadLabels = [];
        foreach ($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " custum-file-upload ")]') as $node) {
            $uploadLabels[] = $node;
        }
        foreach ($uploadLabels as $label) {
            $img = null;
            foreach ($label->getElementsByTagName('img') as $candidate) {
                $src = trim($candidate->getAttribute('src'));
                if ($src !== '') {
                    $img = $candidate;
                    break;
                }
            }
            if ($img) {
                $replacement = $img->cloneNode(true);
                $replacement->removeAttribute('data-upload-preview');
                $replacement->removeAttribute('data-lpb-upload-init');
                $existingClass = trim($replacement->getAttribute('class') ?? '');
                $replacement->setAttribute('class', trim($existingClass . ' img-fluid rounded-3 w-100 h-100'));
                if (!$replacement->getAttribute('alt')) {
                    $replacement->setAttribute('alt', 'image');
                }
                $label->parentNode?->replaceChild($replacement, $label);
                continue;
            }
            $text = trim($label->textContent ?? '');
            if ($text === '') {
                $text = 'Konten teks';
            }
            $placeholder = $dom->createElement('div', $text);
            $placeholder->setAttribute('class', 'text-center text-muted py-4');
            $label->parentNode?->replaceChild($placeholder, $label);
        }

        $fileInputs = [];
        foreach ($xpath->query('//input[@type="file"]') as $input) {
            $fileInputs[] = $input;
        }
        foreach ($fileInputs as $input) {
            if ($input->parentNode) {
                $input->parentNode->removeChild($input);
            }
        }
    }

    private function persistDataImages(string $html): string
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $imgs = $dom->getElementsByTagName('img');
        $baseDir = __DIR__ . '/../../public/uploads/' . date('Y/m');
        $configBaseUrl = '';
        $configFile = __DIR__ . '/../config/config.php';
        if (is_file($configFile)) {
            $cfg = require $configFile;
            $configBaseUrl = rtrim((string)($cfg['base_url'] ?? ''), '/');
        }

        if ($configBaseUrl === '') {
            $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '') ?: '';
            $scriptDir = str_replace('\\', '/', $scriptDir);
            $configBaseUrl = rtrim($scriptDir === '/' ? '' : $scriptDir, '/');
        }

        $baseUrl = ($configBaseUrl === '' ? '' : $configBaseUrl) . '/uploads/' . date('Y/m');

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
