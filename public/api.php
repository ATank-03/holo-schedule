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

/**
 * Extract a YouTube video ID from a URL (supports youtu.be and youtube.com/watch?v=...).
 */
function extractYouTubeVideoId(string $url): ?string
{
    $parts = parse_url($url);
    if ($parts === false) {
        return null;
    }

    $host = strtolower($parts['host'] ?? '');
    if (str_contains($host, 'youtu.be')) {
        $path = trim($parts['path'] ?? '', '/');
        return $path !== '' ? $path : null;
    }

    if (str_contains($host, 'youtube.com')) {
        $query = $parts['query'] ?? '';
        parse_str($query, $qs);
        if (!empty($qs['v']) && is_string($qs['v'])) {
            return $qs['v'];
        }
    }

    return null;
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
                       s.category AS streamer_name
                FROM streams s
                WHERE s.streamer_id = :viewer_id
                  AND s.start_time_utc >= :now
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
            requireFields($input, ['url']);

            $rawUrl = trim((string) $input['url']);
            $videoId = extractYouTubeVideoId($rawUrl);
            if ($videoId === null) {
                respond(['error' => 'Alleen YouTube-links worden ondersteund voor automatisch invullen.'], 422);
            }

            $apiKey = $config['youtube_api_key'] ?? null;
            if (!$apiKey || $apiKey === 'YOUR_YOUTUBE_API_KEY_HERE' || $apiKey === 'AIzaSyAQeLPlEnOyJ499_tjqG-I0PkrJAWrLofY') {
                respond(['error' => 'YouTube API key niet geconfigureerd of ongeldig in config.php. Vervang de placeholder door een geldige API key.'], 500);
            }

            $videosUrl = sprintf(
                'https://www.googleapis.com/youtube/v3/videos?part=snippet,liveStreamingDetails&id=%s&key=%s',
                urlencode($videoId),
                urlencode($apiKey)
            );

            try {
                $videosData = fetchJson($videosUrl);
            } catch (RuntimeException $e) {
                respond(['error' => 'YouTube API aanvraag mislukt: ' . $e->getMessage()], 500);
            }

            $items = isset($videosData['items']) && is_array($videosData['items']) ? $videosData['items'] : [];
            
            // Debug logging for YouTube API issues
            if (!$items && isset($videosData['error'])) {
                error_log('YouTube API Error: ' . print_r($videosData['error'], true));
            }
            
            if (!$items) {
                // Check if there's an error in the response
                if (isset($videosData['error'])) {
                    $errorMsg = $videosData['error']['message'] ?? 'Onbekende YouTube API fout';
                    $errorCode = $videosData['error']['code'] ?? 0;
                    $quotaError = strpos($errorMsg, 'quota') !== false || strpos($errorMsg, 'limit') !== false;
                    
                    $suggestions = [];
                    if ($quotaError) {
                        $suggestions[] = 'Je YouTube API quota is op. Wacht tot het reset (dagelijks) of verhoog je quota in Google Cloud Console.';
                    }
                    if ($errorCode === 403) {
                        $suggestions[] = 'Controleer of je API key is ingeschakeld voor YouTube Data API v3.';
                        $suggestions[] = 'Zorg ervoor dat je factureringsaccount is gekoppeld aan je Google Cloud project.';
                    }
                    
                    $fullMessage = 'YouTube API fout: ' . $errorMsg . ' (code: ' . $errorCode . '). ' . implode(' ', $suggestions);
                    respond(['error' => $fullMessage, 'debug' => ['error' => $videosData['error'], 'video_id' => $videoId]], $errorCode === 403 ? 403 : 500);
                }
                
                // Check if video exists but has no liveStreamingDetails (not a live video)
                if (isset($videosData['items']) && is_array($videosData['items']) && count($videosData['items']) > 0) {
                    $item = $videosData['items'][0];
                    if (!isset($item['liveStreamingDetails'])) {
                        respond(['error' => 'Deze video is geen geplande livestream. Alleen livestreams met een geplande starttijd kunnen worden toegevoegd. Probeer een andere YouTube livestream URL.', 'debug' => ['video_data' => $item]], 422);
                    }
                }
                
                respond(['error' => 'Kon geen informatie vinden voor deze YouTube-video. Controleer of de URL correct is en of de video bestaat. De video moet een geplande livestream zijn met liveStreamingDetails.', 'debug' => ['response' => $videosData, 'video_id' => $videoId]], 404);
            }

            $v = $items[0];
            $snippet = $v['snippet'] ?? [];
            $live = $v['liveStreamingDetails'] ?? [];

            $title = (string) ($snippet['title'] ?? 'YouTube stream');
            $description = (string) ($snippet['description'] ?? '');
            $channelTitle = (string) ($snippet['channelTitle'] ?? '');

            // Bepaal start- en eindtijd: gebruik liveStreamingDetails indien beschikbaar, anders publicatiedatum +2 uur.
            $startIso = null;
            if (isset($live['scheduledStartTime'])) {
                $startIso = (string) $live['scheduledStartTime'];
            } elseif (isset($snippet['publishedAt'])) {
                $startIso = (string) $snippet['publishedAt'];
            }
            if ($startIso === null) {
                $startIso = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DATE_ATOM);
            }

            $start = new DateTimeImmutable($startIso, new DateTimeZone('UTC'));



            $normalizedUrl = 'https://www.youtube.com/watch?v=' . urlencode($videoId);

            // Skip als deze URL al in het persoonlijke schema staat.
            $existsStmt = $pdo->prepare('SELECT id FROM streams WHERE streamer_id = :sid AND url = :url LIMIT 1');
            $existsStmt->execute([
                'sid' => $user['id'],
                'url' => $normalizedUrl,
            ]);
            if ($existsStmt->fetch()) {
                respond(['error' => 'Deze stream staat al in je schema.'], 409);
            }

            try {
                $stmt = $pdo->prepare('INSERT INTO streams (streamer_id, title, description, platform, url, start_time_utc, category, created_at) 
                    VALUES (:streamer_id, :title, :description, :platform, :url, :start_time_utc, :category, :created_at)');
                $success = $stmt->execute([
                    'streamer_id' => $user['id'],
                    'title' => $title,
                    'description' => $description,
                    'platform' => 'YouTube',
                    'url' => $normalizedUrl,
                    'start_time_utc' => $start->format(DATE_ATOM),
                    'category' => $channelTitle,
                    'created_at' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DATE_ATOM),
                ]);

                if (!$success) {
                    $errorInfo = $stmt->errorInfo();
                    respond(['error' => 'Database fout bij opslaan stream: ' . ($errorInfo[2] ?? 'Onbekende fout')], 500);
                }

                respond(['status' => 'ok']);
            } catch (PDOException $e) {
                respond(['error' => 'Database fout: ' . $e->getMessage()], 500);
            } catch (Throwable $e) {
                respond(['error' => 'Onverwachte fout: ' . $e->getMessage()], 500);
            }

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
            if (!$apiKey || $apiKey === 'YOUR_YOUTUBE_API_KEY_HERE' || $apiKey === 'AIzaSyAQeLPlEnOyJ499_tjqG-I0PkrJAWrLofY') {
                respond(['error' => 'YouTube API key niet geconfigureerd of ongeldig in config.php. Vervang de placeholder door een geldige API key.'], 500);
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

                $stmt = $pdo->prepare('INSERT INTO streams (streamer_id, title, description, platform, url, start_time_utc, category, created_at) 
                    VALUES (:streamer_id, :title, :description, :platform, :url, :start_time_utc, :category, :created_at)');
                $stmt->execute([
                    'streamer_id' => $user['id'],
                    'title' => $title,
                    'description' => $description,
                    'platform' => 'YouTube',
                    'url' => $url,
                    'start_time_utc' => $start->format(DATE_ATOM),
                    'category' => $channelTitle,
                    'created_at' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DATE_ATOM),
                ]);

                $imported++;
            }

            respond(['imported_count' => $imported]);

        case 'remove_stream':
            $user = currentUser($pdo);
            if (!$user) {
                respond(['error' => 'Inloggen vereist'], 403);
            }
            requireFields($input, ['stream_id']);
            
            $streamId = (int) $input['stream_id'];
            
            // Verify the stream belongs to the user
            $checkStmt = $pdo->prepare('SELECT id FROM streams WHERE id = :id AND streamer_id = :streamer_id');
            $checkStmt->execute([
                'id' => $streamId,
                'streamer_id' => $user['id']
            ]);
            
            if (!$checkStmt->fetch()) {
                respond(['error' => 'Stream niet gevonden of je hebt geen toegang'], 404);
            }
            
            // Delete the stream
            $deleteStmt = $pdo->prepare('DELETE FROM streams WHERE id = :id');
            $deleteStmt->execute(['id' => $streamId]);
            
            respond(['status' => 'ok', 'message' => 'Stream verwijderd']);

        default:
            respond(['error' => 'Unknown action'], 400);
    }
} catch (Throwable $e) {
    respond(['error' => 'Server error', 'details' => $e->getMessage()], 500);
}

