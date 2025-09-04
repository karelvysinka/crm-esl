#!/usr/bin/env php
<?php
// Quick CRM schema inspection for import planning.
// - Lists relevant tables and columns
// - Dumps Laravel models in app/Models
// Output: storage/app/import_reports/crm_schema.md

$projectRoot = realpath(__DIR__ . '/../../'); // Laravel app root (contains artisan, composer.json)
$reportDir = $projectRoot . '/storage/app/import_reports';
@mkdir($reportDir, 0777, true);

$md = [];
$md[] = '# CRM Schema Inspection';
$md[] = '';

// Detect DB connection from .env
$envPath = $projectRoot . '/.env';
if (!file_exists($envPath)) {
    $md[] = 'No .env found, skipping live DB connection.';
} else {
    // Minimal .env parser (ignores comment lines and supports quoted values)
    $env = [];
    foreach (file($envPath, FILE_IGNORE_NEW_LINES) as $line) {
        $trim = ltrim($line);
        if ($trim === '' || $trim[0] === '#' || $trim[0] === ';') continue;
        if (!strpos($line, '=')) continue;
        $parts = explode('=', $line, 2);
        $key = rtrim($parts[0]);
        $val = ltrim($parts[1]);
        // Strip inline comments starting with space then # or ;
        $val = preg_replace('/\s+[;#].*$/', '', $val);
        $val = trim($val);
        // Remove surrounding quotes if present
        if ((str_starts_with($val, '"') && str_ends_with($val, '"')) || (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
            $val = substr($val, 1, -1);
        }
        $env[$key] = $val;
    }

    $host = $env['DB_HOST'] ?? '127.0.0.1';
    $port = $env['DB_PORT'] ?? '3306';
    $db   = $env['DB_DATABASE'] ?? '';
    $user = $env['DB_USERNAME'] ?? '';
    $pass = $env['DB_PASSWORD'] ?? '';

    if ($db) {
        try {
            $pdo = new PDO("mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $md[] = "Connected to DB: {$db}@{$host}:{$port}";
            $md[] = '';
            // List key tables if exist
            $tables = ['companies', 'contacts', 'leads', 'opportunities', 'deals', 'tasks', 'projects', 'users'];
            foreach ($tables as $t) {
                $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
                $stmt->execute([$t]);
                if ($stmt->fetch()) {
                    $md[] = "## Table: {$t}";
                    $cols = $pdo->query("SHOW COLUMNS FROM `{$t}`")->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($cols as $c) {
                        $md[] = "- {$c['Field']} ({$c['Type']})";
                    }
                    $md[] = '';
                }
            }
        } catch (Throwable $e) {
            $md[] = 'DB connect error: ' . $e->getMessage();
        }
    }
}

// List models if present
$modelsDir = $projectRoot . '/app/Models';
if (is_dir($modelsDir)) {
    $md[] = '## Models';
    foreach (scandir($modelsDir) as $f) {
        if ($f === '.' || $f === '..') continue;
        if (is_file($modelsDir . '/' . $f) && substr($f, -4) === '.php') {
            $md[] = '- ' . $f;
        }
    }
    $md[] = '';
}

file_put_contents($reportDir . '/crm_schema.md', implode("\n", $md));

echo "Report: " . $reportDir . "/crm_schema.md\n";
