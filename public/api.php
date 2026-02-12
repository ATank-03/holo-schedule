<?php

declare(strict_types=1);

use App\Database;

require __DIR__ . '/../vendor/autoload.php';

session_start();

header('Content-Type: application/json');

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

/**
 * Simple helper to perform a GET request and decode JSON.
 *
 * @param string $url
 * @return array<string,mixed>
 */
function fetchJson(string $url): array
{
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 5,
        ],
    ]);

    $raw = @file_get_contents($url, false, $context);
    if ($raw === false) {
        throw new RuntimeException('Kon data niet ophalen van externe API.');
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        throw new RuntimeException('Ongeldig JSON antwoord van externe API.');
    }
    return $data;
}

try {
    $config = require __DIR__ . '/../config.php';
    $db = new Database($config['db_path']);
    $pdo = $db->pdo();

    $input = json_decode(file_get_contents('php://input') ?: '[]', true) ?? [];
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'register':
            requireFields($input, ['email', 'password', 'display_name']);
            $hash = password_hash($input['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, role, display_name, created_at) VALUES (:email, :password_hash, :role, :display_name, :created_at)');
            $stmt->execute([
                'email' => strtolower(trim($input['email'])),
                'password_hash' => $hash,
                'role' => 'viewer',
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

        case 'my_schedule':
            $user = currentUser($pdo);
            if (!$user) {
                respond(['error' => 'Inloggen vereist'], 403);
            }
            $now = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DATE_ATOM);

            $stmt = $pdo->prepare('
                SELECT s.id,
                       s.title,
                       s.platform,
                       s.url,
                       s.start_time_utc,
                       s.end_time_utc,
                       s.category AS streamer_name
                FROM streams s
                WHERE s.streamer_id = :viewer_id
                  AND s.end_time_utc >= :now
                ORDER BY s.start_time_utc ASC
            ');
            $stmt->execute([
                'viewer_id' => $user['id'],
                'now' => $now,
            ]);
            $streams = $stmt->fetchAll();
            respond(['streams' => $streams]);

        case 'add_stream_manual':
            $user = currentUser($pdo);
            if (!$user) {
                respond(['error' => 'Inloggen vereist'], 403);
            }
            requireFields($input, ['title', 'channel_name', 'platform', 'url', 'start_time', 'end_time']);

            $start = new DateTimeImmutable((string) $input['start_time'], new DateTimeZone('UTC'));
            $end = new DateTimeImmutable((string) $input['end_time'], new DateTimeZone('UTC'));
            if ($end <= $start) {
                respond(['error' => 'Eindtijd moet na starttijd liggen'], 422);
            }

            // Prevent overlapping entries in the personal schedule.
            $overlapStmt = $pdo->prepare('SELECT COUNT(*) AS cnt FROM streams WHERE streamer_id = :sid AND NOT (end_time_utc <= :start OR start_time_utc >= :end)');
            $overlapStmt->execute([
                'sid' => $user['id'],
                'start' => $start->format(DATE_ATOM),
                'end' => $end->format(DATE_ATOM),
            ]);
            $overlap = (int) $overlapStmt->fetchColumn();
            if ($overlap > 0) {
                respond(['error' => 'Deze stream overlapt met een bestaande stream in je schema'], 422);
            }

            $stmt = $pdo->prepare('INSERT INTO streams (streamer_id, title, description, platform, url, start_time_utc, end_time_utc, category, created_at) 
                VALUES (:streamer_id, :title, :description, :platform, :url, :start_time_utc, :end_time_utc, :category, :created_at)');
            $stmt->execute([
                'streamer_id' => $user['id'],
                'title' => trim((string) ($input['title'] ?? '')),
                'description' => trim((string) ($input['description'] ?? '')),
                'platform' => (string) $input['platform'],
                'url' => (string) $input['url'],
                'start_time_utc' => $start->format(DATE_ATOM),
                'end_time_utc' => $end->format(DATE_ATOM),
                'category' => trim((string) ($input['channel_name'] ?? '')),
                'created_at' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DATE_ATOM),
            ]);

            respond(['status' => 'ok']);

        case 'import_youtube_streams':
            $user = currentUser($pdo);
            if (!$user) {
                respond(['error' => 'Inloggen vereist'], 403);
            }
            requireFields($input, ['channel_id']);
            $channelId = trim((string) $input['channel_id']);
            if ($channelId === '') {
                respond(['error' => 'Ongeldige channel ID'], 422);
            }

            $apiKey = $config['youtube_api_key'] ?? null;
            if (!$apiKey || $apiKey === 'YOUR_YOUTUBE_API_KEY_HERE') {
                respond(['error' => 'YouTube API key niet geconfigureerd in config.php'], 500);
            }

            // 1) Zoek komende livestreams voor dit kanaal.
            $searchUrl = sprintf(
                'https://www.googleapis.com/youtube/v3/search?part=snippet&channelId=%s&type=video&eventType=upcoming&maxResults=10&key=%s',
                urlencode($channelId),
                urlencode($apiKey)
            );

            $searchData = fetchJson($searchUrl);
            $items = isset($searchData['items']) && is_array($searchData['items']) ? $searchData['items'] : [];
            if (!$items) {
                respond(['imported_count' => 0]);
            }

            $videoIds = [];
            foreach ($items as $item) {
                if (!isset($item['id']['videoId'])) {
                    continue;
                }
                $videoIds[] = $item['id']['videoId'];
            }
            if (!$videoIds) {
                respond(['imported_count' => 0]);
            }

            // 2) Haal details op inclusief geplande starttijd.
            $videosUrl = sprintf(
                'https://www.googleapis.com/youtube/v3/videos?part=snippet,liveStreamingDetails&id=%s&key=%s',
                urlencode(implode(',', $videoIds)),
                urlencode($apiKey)
            );
            $videosData = fetchJson($videosUrl);
            $vItems = isset($videosData['items']) && is_array($videosData['items']) ? $videosData['items'] : [];

            $imported = 0;
            foreach ($vItems as $v) {
                $snippet = $v['snippet'] ?? [];
                $live = $v['liveStreamingDetails'] ?? [];
                $videoId = $v['id'] ?? null;
                if (!$videoId || !isset($live['scheduledStartTime'])) {
                    continue;
                }

                $title = (string) ($snippet['title'] ?? 'YouTube stream');
                $description = (string) ($snippet['description'] ?? '');
                $channelTitle = (string) ($snippet['channelTitle'] ?? '');
                $startIso = (string) $live['scheduledStartTime'];

                try {
                    $start = new DateTimeImmutable($startIso, new DateTimeZone('UTC'));
                } catch (Throwable) {
                    continue;
                }

                // Gebruik eventueel geplande eindtijd, anders +2 uur als ruwe schatting.
                $end = null;
                if (isset($live['scheduledEndTime'])) {
                    try {
                        $end = new DateTimeImmutable((string) $live['scheduledEndTime'], new DateTimeZone('UTC'));
                    } catch (Throwable) {
                        $end = null;
                    }
                }
                if (!$end) {
                    $end = $start->modify('+2 hours');
                }

                $url = 'https://www.youtube.com/watch?v=' . urlencode($videoId);

                // Skip als deze URL al in het persoonlijke schema staat.
                $existsStmt = $pdo->prepare('SELECT id FROM streams WHERE streamer_id = :sid AND url = :url LIMIT 1');
                $existsStmt->execute([
                    'sid' => $user['id'],
                    'url' => $url,
                ]);
                if ($existsStmt->fetch()) {
                    continue;
                }

                $stmt = $pdo->prepare('INSERT INTO streams (streamer_id, title, description, platform, url, start_time_utc, end_time_utc, category, created_at) 
                    VALUES (:streamer_id, :title, :description, :platform, :url, :start_time_utc, :end_time_utc, :category, :created_at)');
                $stmt->execute([
                    'streamer_id' => $user['id'],
                    'title' => $title,
                    'description' => $description,
                    'platform' => 'YouTube',
                    'url' => $url,
                    'start_time_utc' => $start->format(DATE_ATOM),
                    'end_time_utc' => $end->format(DATE_ATOM),
                    'category' => $channelTitle,
                    'created_at' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DATE_ATOM),
                ]);

                $imported++;
            }

            respond(['imported_count' => $imported]);

        default:
            respond(['error' => 'Unknown action'], 400);
    }
} catch (Throwable $e) {
    respond(['error' => 'Server error', 'details' => $e->getMessage()], 500);
}

