<?php
require_once __DIR__ . '/../Core/Logger.php';
require_once __DIR__ . '/../Core/Database.php';
require_once __DIR__ . '/../Models/Page.php';

class PaymentController
{
    private string $serverKey;
    private string $clientKey;
    private string $merchantId;

    public function __construct()
    {
        $this->serverKey = getenv('MIDTRANS_SERVER_KEY') ?: 'Mid-server-kdOomCE3z1IYXJD3C0H7IpoY';
        $this->clientKey = getenv('MIDTRANS_CLIENT_KEY') ?: 'Mid-client-RLb2E0FYogowoTrk';
        $this->merchantId = getenv('MIDTRANS_MERCHANT_ID') ?: 'G370059886';
    }

    public function createQrisPayment(): void
    {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $pageId = (int)($input['page_id'] ?? 0);
        $overrideAmount = isset($input['amount']) ? (int)$input['amount'] : null;
        $overrideName = isset($input['product_name']) ? trim((string)$input['product_name']) : null;

        if ($pageId <= 0) {
            Logger::error('Payment request rejected: missing page_id', ['input' => $input]);
            http_response_code(400);
            echo json_encode(['error' => 'page_id is required']);
            return;
        }

        $page = Page::find($pageId);
        if (!$page) {
            Logger::error('Payment request rejected: page not found', ['page_id' => $pageId]);
            http_response_code(404);
            echo json_encode(['error' => 'Page not found']);
            return;
        }

        $productConfig = json_decode($page['product_config'] ?? '', true) ?: [];
        $grossAmount = $overrideAmount ?? (int)($productConfig['price'] ?? 0);
        $productName = $overrideName !== null && $overrideName !== '' ? $overrideName : ($productConfig['name'] ?? ($page['title'] ?? 'Payment'));

        if ($grossAmount <= 0) {
            Logger::error('Payment request rejected: missing product price', [
                'page_id' => $pageId,
                'product_config' => $productConfig,
            ]);
            http_response_code(400);
            echo json_encode(['error' => 'Product price is required for payment']);
            return;
        }

        $orderId = 'INV-' . $pageId . '-' . time() . '-' . random_int(1000, 9999);

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO payments (
            page_id, order_id, product_name, gross_amount, currency,
            customer_name, customer_phone, customer_email,
            payment_type, provider, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $pageId,
            $orderId,
            substr($productName, 0, 160),
            $grossAmount,
            'IDR',
            null,
            null,
            null,
            'qris',
            null,
            'pending',
        ]);

        $payload = [
            'payment_type' => 'qris',
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $grossAmount,
            ],
        ];

        $response = $this->midtransCharge($payload);
        if (!$response || !isset($response['transaction_id'])) {
            Logger::error('Midtrans charge failed', [
                'page_id' => $pageId,
                'order_id' => $orderId,
                'response' => $response,
            ]);
            $update = $pdo->prepare('UPDATE payments SET status = ?, metadata = ? WHERE order_id = ?');
            $update->execute(['failure', json_encode(['error' => $response]), $orderId]);
            http_response_code(502);
            echo json_encode(['error' => 'Failed to create payment']);
            return;
        }

        $expiryTime = $response['expiry_time'] ?? null;
        $expiryAt = $expiryTime ? date('Y-m-d H:i:s', strtotime($expiryTime)) : null;
        $qrUrl = $response['actions'][0]['url'] ?? ($response['qr_url'] ?? null);
        $qrString = $response['actions'][0]['qr_string'] ?? ($response['qr_string'] ?? null);
        $status = $this->mapMidtransStatus($response['transaction_status'] ?? 'pending');
        $metadata = json_encode($response);

        $update = $pdo->prepare('UPDATE payments SET
            provider = ?, midtrans_transaction_id = ?, midtrans_status = ?, qr_string = ?, qr_url = ?, expiry_time = ?, metadata = ?, status = ?, updated_at = NOW()
            WHERE order_id = ?');
        $update->execute([
            $response['payment_type'] ?? 'qris',
            $response['transaction_id'] ?? null,
            $response['transaction_status'] ?? null,
            $qrString,
            $qrUrl,
            $expiryAt,
            $metadata,
            $status,
            $orderId,
        ]);

        echo json_encode([
            'order_id' => $orderId,
            'qr_url' => $qrUrl ?? '',
            'expiry_time' => $expiryTime ?? null,
            'status' => $status,
        ]);
    }

    public function getStatus(string $orderId): void
    {
        header('Content-Type: application/json');
        if ($orderId === '') {
            http_response_code(400);
            echo json_encode(['error' => 'order_id is required']);
            return;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT status, paid_at FROM payments WHERE order_id = ? LIMIT 1');
        $stmt->execute([$orderId]);
        $payment = $stmt->fetch();
        if (!$payment) {
            http_response_code(404);
            echo json_encode(['error' => 'Payment not found']);
            return;
        }
        echo json_encode([
            'order_id' => $orderId,
            'status' => $payment['status'],
            'paid_at' => $payment['paid_at'],
        ]);
    }

    public function handleWebhook(): void
    {
        header('Content-Type: application/json');
        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true) ?? [];
        $orderId = $payload['order_id'] ?? '';

        if ($orderId === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid payload']);
            return;
        }

        $signatureKey = $payload['signature_key'] ?? '';
        $expectedSignature = hash(
            'sha512',
            $orderId . ($payload['status_code'] ?? '') . ($payload['gross_amount'] ?? '') . $this->serverKey
        );
        if ($signatureKey === '' || !hash_equals($expectedSignature, $signatureKey)) {
            Logger::error('Midtrans webhook rejected: invalid signature', [
                'order_id' => $orderId,
                'signature_key' => $signatureKey,
            ]);
            http_response_code(401);
            echo json_encode(['error' => 'Invalid signature']);
            return;
        }

        $midtransStatus = $payload['transaction_status'] ?? 'pending';
        $status = $this->mapMidtransStatus($midtransStatus);
        $paidAt = null;
        if ($status === 'settlement') {
            $paidAt = date('Y-m-d H:i:s');
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE payments SET status = ?, paid_at = ?, midtrans_status = ?, metadata = ?, updated_at = NOW() WHERE order_id = ?');
        $stmt->execute([
            $status,
            $paidAt,
            $midtransStatus,
            json_encode($payload),
            $orderId,
        ]);

        echo json_encode(['ok' => true]);
    }

    private function midtransCharge(array $payload): ?array
    {
        $ch = curl_init('https://api.sandbox.midtrans.com/v2/charge');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode($this->serverKey . ':'),
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $caFile = 'D:/laragon/etc/ssl/cacert.pem';
        if (is_file($caFile)) {
            curl_setopt($ch, CURLOPT_CAINFO, $caFile);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }

        $res = curl_exec($ch);
        if ($res === false) {
            Logger::error('Midtrans charge curl error', [
                'error' => curl_error($ch),
                'payload_summary' => [
                    'order_id' => $payload['transaction_details']['order_id'] ?? null,
                    'gross_amount' => $payload['transaction_details']['gross_amount'] ?? null,
                    'payment_type' => $payload['payment_type'] ?? null,
                ],
            ]);
            curl_close($ch);
            return null;
        }
        $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if ($code >= 200 && $code < 300) {
            return json_decode($res, true);
        }
        Logger::error('Midtrans charge returned non-2xx', [
            'http_code' => $code,
            'response_body' => $res,
            'payload_summary' => [
                'order_id' => $payload['transaction_details']['order_id'] ?? null,
                'gross_amount' => $payload['transaction_details']['gross_amount'] ?? null,
                'payment_type' => $payload['payment_type'] ?? null,
            ],
        ]);
        return null;
    }

    private function mapMidtransStatus(string $midtransStatus): string
    {
        switch ($midtransStatus) {
            case 'settlement':
            case 'capture':
                return 'settlement';
            case 'pending':
                return 'pending';
            case 'expire':
                return 'expire';
            case 'cancel':
                return 'cancel';
            case 'deny':
            case 'failure':
                return 'failure';
            default:
                return 'pending';
        }
    }
}
