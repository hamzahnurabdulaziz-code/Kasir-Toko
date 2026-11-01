<?php

// Initialize writable storage directories in /tmp for Vercel Serverless environment
$storagePath = sys_get_temp_dir() . '/storage';
$subdirs = [
    $storagePath . '/framework/views',
    $storagePath . '/framework/cache/data',
    $storagePath . '/framework/sessions',
    $storagePath . '/logs',
    sys_get_temp_dir() . '/bootstrap/cache',
];

foreach ($subdirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// Copy pre-seeded SQLite database to temp dir if using SQLite
$defaultDb = __DIR__ . '/../database/database.sqlite';
$targetDb = sys_get_temp_dir() . '/database.sqlite';
if (file_exists($defaultDb) && !file_exists($targetDb)) {
    @copy($defaultDb, $targetDb);
} elseif (!file_exists($targetDb)) {
    @touch($targetDb);
}

// Set storage path for Laravel
$_ENV['LARAVEL_STORAGE_PATH'] = $storagePath;
$_SERVER['LARAVEL_STORAGE_PATH'] = $storagePath;

// Forward request to Laravel public/index.php
require __DIR__ . '/../public/index.php';
