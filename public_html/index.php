<?php

declare(strict_types=1);

if (php_sapi_name() === 'cli-server') {
    $filePath = __DIR__ . parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    if (is_file($filePath)) {
        return false;
    }
}

use App\Kernel;
use App\Service\PluginManager;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;

$appCoreDir = dirname(__DIR__) . '/app-core';

// Check composer autoload
$autoloadFile = $appCoreDir . '/vendor/autoload.php';
if (!file_exists($autoloadFile)) {
    http_response_code(500);
    echo '<!DOCTYPE html><html><body style="font-family:sans-serif;padding:2rem;">';
    echo '<h2>Modern Standalone CMS Error</h2>';
    echo '<p>Autoloader not found. Please ensure <code>app-core/vendor/</code> is uploaded.</p>';
    echo '</body></html>';
    exit(1);
}

require_once $autoloadFile;

// Load environment variables if available
if (file_exists($appCoreDir . '/.env')) {
    (new Dotenv())->bootEnv($appCoreDir . '/.env');
}

// Check installed.lock
$lockFile = $appCoreDir . '/var/installed.lock';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$isInstallRoute = str_starts_with($requestUri, '/install');
$isAssetRoute = str_starts_with($requestUri, '/build/') || str_starts_with($requestUri, '/uploads/');

if (!file_exists($lockFile) && !$isInstallRoute && !$isAssetRoute) {
    header('Location: /install');
    exit;
}

// Environment & Debug
$env = $_SERVER['APP_ENV'] ?? 'dev';
$debug = (bool) ($_SERVER['APP_DEBUG'] ?? true);

$kernel = new Kernel($env, $debug);
$request = Request::createFromGlobals();

// Boot active plugins if CMS is installed
if (file_exists($lockFile)) {
    $kernel->boot();
    $container = $kernel->getContainer();
    if ($container->has(PluginManager::class)) {
        /** @var PluginManager $pluginManager */
        $pluginManager = $container->get(PluginManager::class);
        $pluginManager->bootActivePlugins();
    }
}

$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
