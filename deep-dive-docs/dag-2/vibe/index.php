<?php

declare(strict_types=1);

// Simple, framework-less REST API for a library system.
// Uses in-memory sample data and aligns with the Mermaid diagrams
// in `mermaid-code` (books, users/members, loans, reservations).

require_once __DIR__ . '/src/LibraryService.php';

use App\LibraryService;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$service = new LibraryService();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';

// Strip query string and leading/trailing slashes
$path = explode('?', $uri, 2)[0];
$path = trim($path, '/');
$segments = $path === '' ? [] : explode('/', $path);

try {
    if ($method === 'GET' && ($path === '' || $path === 'health')) {
        echo json_encode(['status' => 'ok', 'service' => 'library-api']);
        exit;
    }

    // GET /books?query=1984  (search) or GET /books (list)
    if ($method === 'GET' && $path === 'books') {
        $query = $_GET['query'] ?? '';
        $books = $service->searchBooks($query);
        echo json_encode($books);
        exit;
    }

    // GET /books/{id}
    if ($method === 'GET' && count($segments) === 2 && $segments[0] === 'books') {
        $id = (int) $segments[1];
        $book = $service->getBookById($id);
        if ($book === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Book not found']);
            exit;
        }
        echo json_encode($book);
        exit;
    }

    // GET /categories
    if ($method === 'GET' && $path === 'categories') {
        echo json_encode($service->getCategories());
        exit;
    }

    // GET /categories/{id}/books
    if (
        $method === 'GET'
        && count($segments) === 3
        && $segments[0] === 'categories'
        && $segments[2] === 'books'
    ) {
        $categoryId = (int) $segments[1];
        echo json_encode($service->getBooksByCategory($categoryId));
        exit;
    }

    // POST /borrow  { "userId": 1, "bookId": 123 }
    if ($method === 'POST' && $path === 'borrow') {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        if (!isset($data['userId'], $data['bookId'])) {
            http_response_code(400);
            echo json_encode(['error' => 'userId and bookId are required']);
            exit;
        }

        $result = $service->borrowBook((int) $data['userId'], (int) $data['bookId']);
        if ($result['success'] === false) {
            http_response_code($result['statusCode'] ?? 400);
        }
        echo json_encode($result);
        exit;
    }

    // POST /return  { "userId": 1, "bookId": 123 }
    if ($method === 'POST' && $path === 'return') {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        if (!isset($data['userId'], $data['bookId'])) {
            http_response_code(400);
            echo json_encode(['error' => 'userId and bookId are required']);
            exit;
        }

        $result = $service->returnBook((int) $data['userId'], (int) $data['bookId']);
        if ($result['success'] === false) {
            http_response_code($result['statusCode'] ?? 400);
        }
        echo json_encode($result);
        exit;
    }

    // GET /users/{id}/loans
    if ($method === 'GET' && count($segments) === 3 && $segments[0] === 'users' && $segments[2] === 'loans') {
        $userId = (int) $segments[1];
        echo json_encode($service->getLoansForUser($userId));
        exit;
    }

    // GET /users/{id}/reservations
    if (
        $method === 'GET'
        && count($segments) === 3
        && $segments[0] === 'users'
        && $segments[2] === 'reservations'
    ) {
        $userId = (int) $segments[1];
        echo json_encode($service->getReservationsForUser($userId));
        exit;
    }

    http_response_code(404);
    echo json_encode(['error' => 'Route not found']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage(),
    ]);
}

