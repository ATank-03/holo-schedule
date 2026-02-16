<?php

declare(strict_types=1);

use App\Database;

require __DIR__ . '/../vendor/autoload.php';

session_start();

header('Content-Type: application/json');

$config = require __DIR__ . '/../config.php';
$db = new Database($config['db_path']);
$pdo = $db->pdo();

$input = json_decode(file_get_contents('php://input') ?: '[]', true) ?? [];
$action = $_GET['action'] ?? '';

function respond(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function requireFields(array $input, array $fields): void
{
    foreach ($fields as $field) {
        if (!isset($input[$field]) || $input[$field] === '') {
            respond(['error' => "Missing field: $field"], 422);
        }
    }
}

function currentUser(PDO $pdo): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $stmt = $pdo->prepare('SELECT id, email, display_name, role FROM users WHERE id = :id');
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();
    return $user ?: null;
}

try {
    switch ($action) {
        case 'register':
            requireFields($input, ['email', 'password', 'display_name', 'role']);
            if (!in_array($input['role'], ['viewer', 'streamer'], true)) {
                respond(['error' => 'Invalid role'], 422);
            }

            $hash = password_hash($input['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, role, display_name, created_at) VALUES (:email, :password_hash, :role, :display_name, :created_at)');
            $stmt->execute([
                'email' => strtolower(trim($input['email'])),
                'password_hash' => $hash,
                'role' => $input['role'],
                'display_name' => trim($input['display_name']),
                'created_at' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DATE_ATOM),
            ]);
            respond(['status' => 'ok']);

        case 'login':
            requireFields($input, ['email', 'password']);
            $stmt = $pdo->prepare('SELECT id, email, password_hash, role, display_name FROM users WHERE email = :email');
            $stmt->execute(['email' => strtolower(trim($input['email']))]);
            $user = $stmt->fetch();
            if (!$user || !password_verify($input['password'], $user['password_hash'])) {
                respond(['error' => 'Ongeldige inloggegevens'], 401);
            }
            $_SESSION['user_id'] = $user['id'];
            unset($user['password_hash']);
            respond(['user' => $user]);

        case 'me':
            $user = currentUser($pdo);
            respond(['user' => $user]);

        case 'logout':
            $_SESSION = [];
            if (session_id() !== '') {
                session_destroy();
            }
            respond(['status' => 'ok']);

        case 'create_stream':
            $user = currentUser($pdo);
            if (!$user || $user['role'] !== 'streamer') {
                respond(['error' => 'Alleen streamers kunnen streams aanmaken'], 403);
            }
            requireFields($input, ['title', 'platform', 'url', 'start_time', 'end_time']);

            $start = new DateTimeImmutable($input['start_time'], new DateTimeZone('UTC'));
            $end = new DateTimeImmutable($input['end_time'], new DateTimeZone('UTC'));
            if ($end <= $start) {
                respond(['error' => 'Eindtijd moet na starttijd liggen'], 422);
            }

            // Very basic validation for overlapping streams for this streamer.
            $overlapStmt = $pdo->prepare('SELECT COUNT(*) AS cnt FROM streams WHERE streamer_id = :sid AND NOT (end_time_utc <= :start OR start_time_utc >= :end)');
            $overlapStmt->execute([
                'sid' => $user['id'],
                'start' => $start->format(DATE_ATOM),
                'end' => $end->format(DATE_ATOM),
            ]);
            $overlap = (int) $overlapStmt->fetchColumn();
            if ($overlap > 0) {
                respond(['error' => 'Deze stream overlapt met een bestaande stream'], 422);
            }

            $stmt = $pdo->prepare('INSERT INTO streams (streamer_id, title, description, platform, url, start_time_utc, end_time_utc, created_at) 
                VALUES (:streamer_id, :title, :description, :platform, :url, :start_time_utc, :end_time_utc, :created_at)');
            $stmt->execute([
                'streamer_id' => $user['id'],
                'title' => trim((string) ($input['title'] ?? '')),
                'description' => trim((string) ($input['description'] ?? '')),
                'platform' => (string) $input['platform'],
                'url' => (string) $input['url'],
                'start_time_utc' => $start->format(DATE_ATOM),
                'end_time_utc' => $end->format(DATE_ATOM),
                'created_at' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DATE_ATOM),
            ]);

            respond(['status' => 'ok']);

        case 'my_streams':
            $user = currentUser($pdo);
            if (!$user || $user['role'] !== 'streamer') {
                respond(['error' => 'Alleen streamers'], 403);
            }
            $stmt = $pdo->prepare('SELECT id, title, platform, url, start_time_utc, end_time_utc FROM streams WHERE streamer_id = :sid ORDER BY start_time_utc ASC');
            $stmt->execute(['sid' => $user['id']]);
            $streams = $stmt->fetchAll();
            respond(['streams' => $streams]);

        case 'follow':
            $user = currentUser($pdo);
            if (!$user || $user['role'] !== 'viewer') {
                respond(['error' => 'Alleen viewers kunnen volgen'], 403);
            }
            requireFields($input, ['streamer_id']);
            $streamerId = (int) $input['streamer_id'];

            // Ensure target is a streamer
            $stmt = $pdo->prepare("SELECT id FROM users WHERE id = :id AND role = 'streamer'");
            $stmt->execute(['id' => $streamerId]);
            if (!$stmt->fetch()) {
                respond(['error' => 'Streamer niet gevonden'], 404);
            }

            $stmt = $pdo->prepare('INSERT OR IGNORE INTO follows (viewer_id, streamer_id, created_at) VALUES (:viewer_id, :streamer_id, :created_at)');
            $stmt->execute([
                'viewer_id' => $user['id'],
                'streamer_id' => $streamerId,
                'created_at' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DATE_ATOM),
            ]);

            respond(['status' => 'ok']);

        case 'my_schedule':
            $user = currentUser($pdo);
            if (!$user || $user['role'] !== 'viewer') {
                respond(['error' => 'Alleen viewers'], 403);
            }
            $now = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DATE_ATOM);

            $stmt = $pdo->prepare('
                SELECT s.id,
                       s.title,
                       s.platform,
                       s.url,
                       s.start_time_utc,
                       s.end_time_utc,
                       u.display_name AS streamer_name
                FROM streams s
                JOIN follows f ON f.streamer_id = s.streamer_id
                JOIN users u ON u.id = s.streamer_id
                WHERE f.viewer_id = :viewer_id
                  AND s.end_time_utc >= :now
                ORDER BY s.start_time_utc ASC
            ');
            $stmt->execute([
                'viewer_id' => $user['id'],
                'now' => $now,
            ]);
            $streams = $stmt->fetchAll();
            respond(['streams' => $streams]);

        case 'search_streamers':
            $user = currentUser($pdo);
            if (!$user) {
                respond(['error' => 'Inloggen vereist'], 403);
            }
            requireFields($input, ['query']);
            $q = trim((string) $input['query']);
            if ($q === '') {
                respond(['streamers' => []]);
            }

            $like = '%' . $q . '%';
            $idParam = ctype_digit($q) ? (int) $q : -1;

            $stmt = $pdo->prepare('
                SELECT id, display_name, role
                FROM users
                WHERE role = ''streamer''
                  AND (display_name LIKE :q OR id = :id)
                ORDER BY display_name ASC
                LIMIT 20
            ');
            $stmt->execute([
                'q' => $like,
                'id' => $idParam,
            ]);
            $streamers = $stmt->fetchAll();
            respond(['streamers' => $streamers]);

        default:
            respond(['error' => 'Unknown action'], 400);
    }
} catch (Throwable $e) {
    respond(['error' => 'Server error', 'details' => $e->getMessage()], 500);
}

