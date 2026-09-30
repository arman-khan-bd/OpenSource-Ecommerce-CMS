<?php

declare(strict_types=1);

// Bootstrap redirector to /install
$appCoreDir = dirname(__DIR__) . '/app-core';
$lockFile = $appCoreDir . '/var/installed.lock';

if (file_exists($lockFile)) {
    header('Location: /admin/login');
    exit;
}

// Redirect cleanly to /install
header('Location: /install');
exit;
