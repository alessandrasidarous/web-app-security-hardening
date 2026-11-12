<?php
include_once __DIR__ . '/../log/function_log.php';

function loadEnv($path) {
    if (!file_exists($path)) {
        LogErrore(".env file not found");
        exit();
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // salta commenti
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

loadEnv(__DIR__ . '/.env');

$pepper = $_ENV['PEPPER_SECRET'] ?? null;

if ($pepper === null) {
    LogErrore("Pepper not found in environment variables");
    exit();
}

