<?php

declare(strict_types=1);

use App\Database;

require __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../config.php';
$db = new Database($config['db_path']);
$pdo = $db->pdo();

$pdo->exec("
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL CHECK(role IN ('viewer', 'streamer')),
    display_name TEXT NOT NULL,
    timezone TEXT DEFAULT 'UTC',
    created_at TEXT NOT NULL
);
");

// Base definition (end_time_utc nullable so open-ended streams are allowed).
$pdo->exec("
CREATE TABLE IF NOT EXISTS streams (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    streamer_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    description TEXT,
    platform TEXT NOT NULL,
    url TEXT NOT NULL,
    start_time_utc TEXT NOT NULL,
    end_time_utc TEXT,
    category TEXT,
    is_recurring INTEGER DEFAULT 0,
    recurrence_rule TEXT,
    created_at TEXT NOT NULL,
    FOREIGN KEY(streamer_id) REFERENCES users(id)
);
");

// If the table already existed with end_time_utc marked NOT NULL, migrate it to allow NULL.
$stmt = $pdo->query("PRAGMA table_info(streams)");
$columns = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
$endCol = null;
foreach ($columns as $col) {
    if (($col['name'] ?? null) === 'end_time_utc') {
        $endCol = $col;
        break;
    }
}

if ($endCol !== null && (int) ($endCol['notnull'] ?? 0) === 1) {
    // Perform a lightweight migration to drop the NOT NULL constraint on end_time_utc.
    $pdo->beginTransaction();
    $pdo->exec("
        CREATE TABLE streams_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            streamer_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            description TEXT,
            platform TEXT NOT NULL,
            url TEXT NOT NULL,
            start_time_utc TEXT NOT NULL,
            end_time_utc TEXT,
            category TEXT,
            is_recurring INTEGER DEFAULT 0,
            recurrence_rule TEXT,
            created_at TEXT NOT NULL,
            FOREIGN KEY(streamer_id) REFERENCES users(id)
        );
    ");
    $pdo->exec("
        INSERT INTO streams_new (id, streamer_id, title, description, platform, url, start_time_utc, end_time_utc, category, is_recurring, recurrence_rule, created_at)
        SELECT id, streamer_id, title, description, platform, url, start_time_utc, end_time_utc, category, is_recurring, recurrence_rule, created_at
        FROM streams;
    ");
    $pdo->exec("DROP TABLE streams;");
    $pdo->exec("ALTER TABLE streams_new RENAME TO streams;");
    $pdo->commit();
}

$pdo->exec("
CREATE TABLE IF NOT EXISTS follows (
    viewer_id INTEGER NOT NULL,
    streamer_id INTEGER NOT NULL,
    created_at TEXT NOT NULL,
    PRIMARY KEY (viewer_id, streamer_id),
    FOREIGN KEY(viewer_id) REFERENCES users(id),
    FOREIGN KEY(streamer_id) REFERENCES users(id)
);
");

echo "Migrations executed.\n";

