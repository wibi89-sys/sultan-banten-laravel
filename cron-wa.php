<?php

/**
 * Sultan Banten - WhatsApp Cron Automation Bridge
 *
 * This file allows running WhatsApp cron jobs via CLI:
 *   /usr/local/bin/php /home/ditjenpa/sultan_app/cron-wa.php run
 *
 * Or via HTTP / Webhook:
 *   https://sultan.ditjenpasbanten.com/cron-wa?key=ditjenpas_banten_2026_secret&action=run
 */
define('LARAVEL_START', microtime(true));

// Set timezone to Western Indonesia Time (WIB)
date_default_timezone_set('Asia/Jakarta');

// Change working directory to project root
chdir(__DIR__);

// Autoload composer
if (file_exists(__DIR__.'/vendor/autoload.php')) {
    require __DIR__.'/vendor/autoload.php';
} else {
    fwrite(STDERR, "Error: vendor/autoload.php not found. Please run composer install.\n");
    exit(1);
}

// Bootstrap Laravel
/** @var Application $app */
$app = require_once __DIR__.'/bootstrap/app.php';

use App\Services\WhatsAppService;
use Illuminate\Foundation\Application;

if (PHP_SAPI === 'cli') {
    // Running from CLI (Crontab)
    $action = $argv[1] ?? 'run';
    echo '['.date('Y-m-d H:i:s')."] Running WhatsApp Cron Task with action: {$action}...\n";

    try {
        $result = WhatsAppService::runCron($action);
        echo '['.date('Y-m-d H:i:s').'] Result: '.json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n";

        $logPath = __DIR__.'/storage/logs/cron-wa.log';
        if (! is_dir(dirname($logPath))) {
            @mkdir(dirname($logPath), 0755, true);
        }
        $logLine = sprintf(
            "[%s] CRON-WA.PHP - Action: %s - Result: %s\n",
            date('Y-m-d H:i:s'),
            $action,
            json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
        @file_put_contents($logPath, $logLine, FILE_APPEND);

        exit(! empty($result['success']) ? 0 : 1);
    } catch (Throwable $e) {
        $errorMsg = '['.date('Y-m-d H:i:s').'] Exception: '.$e->getMessage()."\n".$e->getTraceAsString()."\n";
        fwrite(STDERR, $errorMsg);
        @file_put_contents(__DIR__.'/storage/logs/cron-wa.log', $errorMsg, FILE_APPEND);
        exit(1);
    }
} else {
    // Running from HTTP / Web
    header('Content-Type: application/json; charset=utf-8');

    $secretKey = env('CRON_SECRET_KEY', 'ditjenpas_banten_2026_secret');
    $providedKey = (string) ($_GET['key'] ?? '');

    if (! hash_equals($secretKey, $providedKey)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid secret key']);
        exit;
    }

    $action = (string) ($_GET['action'] ?? 'run');

    try {
        $result = WhatsAppService::runCron($action);
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}
