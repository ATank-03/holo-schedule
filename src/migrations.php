<?php

declare(strict_types=1);

use App\Database;

require __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../config.php';
$db = new Database($config['db_path']);
$pdo = $db->pdo();

$pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL CHECK(role IN ('viewer', 'streamer')),
    display_name TEXT NOT NULL,
    timezone TEXT DEFAULT 'UTC',
    created_at TEXT NOT NULL
);");

// Base definition without end_time_utc.
$pdo->exec("CREATE TABLE IF NOT EXISTS streams (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    streamer_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    description TEXT,
    platform TEXT NOT NULL,
    url TEXT NOT NULL,
    start_time_utc TEXT NOT NULL,
    category TEXT,
    is_recurring INTEGER DEFAULT 0,
    recurrence_rule TEXT,
    created_at TEXT NOT NULL,
    FOREIGN KEY(streamer_id) REFERENCES users(id)
);");

$pdo->exec("CREATE TABLE IF NOT EXISTS follows (
    viewer_id INTEGER NOT NULL,
    streamer_id INTEGER NOT NULL,
    created_at TEXT NOT NULL,
    PRIMARY KEY (viewer_id, streamer_id),
    FOREIGN KEY(viewer_id) REFERENCES users(id),
    FOREIGN KEY(streamer_id) REFERENCES users(id)
);");

echo "Migrations executed.\n";