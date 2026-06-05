<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

class AdminAiSyncController extends Controller
{
    private function ensureAdmin(): void
    {
        $user = $_SESSION['user'] ?? null;
        if (!$user || $user['role'] !== 'admin') {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }
    }

    public function index(): void
    {
        $this->ensureAdmin();
        $config = $this->loadChatConfig();

        $this->view('admin/ai_sync', [
            'syncWebhookUrl' => (string) ($config['rag_sync_webhook_url'] ?? ''),
        ]);
    }

    public function sync(): void
    {
        $this->ensureAdmin();
        $config = $this->loadChatConfig();
        $webhookUrl = trim((string) ($config['rag_sync_webhook_url'] ?? ''));

        if ($webhookUrl === '') {
            $_SESSION['error'] = 'Chua cau hinh rag_sync_webhook_url trong config/chat.php.';
            $this->redirect('/admin/ai-sync');
        }

        $documents = (new AiDocumentsController())->documents();
        $payload = json_encode([
            'documents' => $documents,
            'generated_at' => gmdate('c'),
        ], JSON_UNESCAPED_UNICODE);

        if ($payload === false) {
            $_SESSION['error'] = 'Khong tao duoc du lieu sync.';
            $this->redirect('/admin/ai-sync');
        }

        $response = $this->postJson($webhookUrl, $payload);
        if ($response === null) {
            $_SESSION['error'] = 'Khong gui duoc du lieu sang n8n sync webhook.';
            $this->redirect('/admin/ai-sync');
        }

        $_SESSION['success'] = 'Da gui ' . count($documents) . ' documents sang n8n de dong bo RAG.';
        $this->redirect('/admin/ai-sync');
    }

    private function loadChatConfig(): array
    {
        $path = dirname(__DIR__, 2) . '/config/chat.php';
        if (!is_file($path)) {
            return [];
        }

        $config = require $path;
        return is_array($config) ? $config : [];
    }

    private function postJson(string $url, string $json): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch === false) {
                return null;
            }

            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $json,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
            ]);

            $body = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($body === false || $httpCode < 200 || $httpCode >= 300) {
                return null;
            }

            return (string) $body;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
                'content' => $json,
                'timeout' => 60,
                'ignore_errors' => true,
            ],
        ]);

        $body = @file_get_contents($url, false, $context);
        return $body === false ? null : (string) $body;
    }
}
