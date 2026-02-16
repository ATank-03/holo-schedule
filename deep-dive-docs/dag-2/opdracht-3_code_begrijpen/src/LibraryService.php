<?php

declare(strict_types=1);

namespace App;

use DateInterval;
use DateTimeImmutable;

/**
 * Simple in-memory library domain service.
 *
 * This class is designed to match the concepts in:
 * - ERD (`library-ERD.mmd`): BOOK, MEMBER/USER, LOAN, RESERVATION, CATEGORY, AUTHOR
 * - Class diagram (`class-diagram.mmd`): User, Book, Category, Loan, Reservation, Review, Penalty
 * - Sequence diagram (`sequence-diagram.mmd`): search books, borrow book, return book
 *
 * NOTE: Data is in-memory only and resets on every request.
 */
class LibraryService
{
    /** @var array<int, array<string, mixed>> */
    private array $users = [];

    /** @var array<int, array<string, mixed>> */
    private array $books = [];

    /** @var array<int, array<string, mixed>> */
    private array $categories = [];

    /** @var array<int, array<string, mixed>> */
    private array $loans = [];

    /** @var array<int, array<string, mixed>> */
    private array $reservations = [];

    public function __construct()
    {
        $this->seedData();
    }

    /**
     * Seed the in-memory data with a small sample based on the diagrams.
     */
    private function seedData(): void
    {
        $this->users = [
            1 => [
                'id' => 1,
                'name' => 'Alice Reader',
                'email' => 'alice@example.com',
                'status' => 'ACTIVE', // See UserStatus note in class diagram
            ],
            2 => [
                'id' => 2,
                'name' => 'Bob Borrower',
                'email' => 'bob@example.com',
                'status' => 'ACTIVE',
            ],
        ];

        $this->categories = [
            1 => ['id' => 1, 'name' => 'Dystopian'],
            2 => ['id' => 2, 'name' => 'Science Fiction'],
            3 => ['id' => 3, 'name' => 'Fantasy'],
        ];

        $this->books = [
            1 => [
                'id' => 1,
                'title' => '1984',
                'author' => 'George Orwell',
                'description' => 'A dystopian novel about a totalitarian regime that controls every aspect of life.',
                'isbn' => '9780451524935',
                'available' => true,
                'categories' => [$this->categories[1], $this->categories[2]],
                'copies_available' => 3,
                'total_copies' => 3,
            ],
            2 => [
                'id' => 2,
                'title' => 'Brave New World',
                'author' => 'Aldous Huxley',
                'description' => 'A science fiction classic about a future society built on genetic engineering and conditioning.',
                'isbn' => '9780060850524',
                'available' => true,
                'categories' => [$this->categories[1]],
                'copies_available' => 1,
                'total_copies' => 1,
            ],
            3 => [
                'id' => 3,
                'title' => 'The Hobbit',
                'author' => 'J.R.R. Tolkien',
                'description' => 'A fantasy adventure following Bilbo Baggins on a journey to reclaim a lost dwarf kingdom.',
                'isbn' => '9780547928227',
                'available' => true,
                'categories' => [$this->categories[3]],
                'copies_available' => 2,
                'total_copies' => 2,
            ],
        ];

        $this->loans = [];
        $this->reservations = [];
    }

    /**
     * Search books by title (name) and description.
     *
     * Mirrors the "Search Books" use case and
     * GET /books?query= from the sequence diagram.
     *
     * @return array<int, array<string, mixed>>
     */
    public function searchBooks(string $query): array
    {
        if ($query === '') {
            return array_values($this->books);
        }

        $queryLower = mb_strtolower($query);

        return array_values(array_filter(
            $this->books,
            static function (array $book) use ($queryLower): bool {
                $title = $book['title'] ?? '';
                $description = $book['description'] ?? '';
                $haystack = mb_strtolower($title . ' ' . $description);
                return str_contains($haystack, $queryLower);
            }
        ));
    }

    /**
     * Get a single book by its ID.
     */
    public function getBookById(int $id): ?array
    {
        return $this->books[$id] ?? null;
    }

    /**
     * Get all categories.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCategories(): array
    {
        return array_values($this->categories);
    }

    /**
     * Get all books that belong to a given category.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getBooksByCategory(int $categoryId): array
    {
        return array_values(array_filter(
            $this->books,
            static function (array $book) use ($categoryId): bool {
                foreach ($book['categories'] as $category) {
                    if (($category['id'] ?? null) === $categoryId) {
                        return true;
                    }
                }
                return false;
            }
        ));
    }

    /**
     * Borrow a book for a user.
     *
     * Mirrors POST /borrow from the sequence diagram and uses
     * LOAN entity concepts from the ERD/class diagram.
     *
     * @return array<string, mixed>
     */
    public function borrowBook(int $userId, int $bookId): array
    {
        $user = $this->users[$userId] ?? null;
        if ($user === null) {
            return [
                'success' => false,
                'statusCode' => 404,
                'error' => 'User not found',
            ];
        }

        if (($user['status'] ?? 'ACTIVE') !== 'ACTIVE') {
            return [
                'success' => false,
                'statusCode' => 409,
                'error' => 'User is not allowed to borrow books',
            ];
        }

        $book = $this->books[$bookId] ?? null;
        if ($book === null) {
            return [
                'success' => false,
                'statusCode' => 404,
                'error' => 'Book not found',
            ];
        }

        if (($book['copies_available'] ?? 0) <= 0) {
            return [
                'success' => false,
                'statusCode' => 409,
                'error' => 'No copies available',
            ];
        }

        // Decrease available copies
        $this->books[$bookId]['copies_available']--;
        $this->books[$bookId]['available'] = $this->books[$bookId]['copies_available'] > 0;

        // Create a new loan
        $loanId = count($this->loans) + 1;
        $loanDate = new DateTimeImmutable();
        $dueDate = $loanDate->add(new DateInterval('P14D')); // 2 weeks

        $loan = [
            'id' => $loanId,
            'userId' => $userId,
            'bookId' => $bookId,
            'loanDate' => $loanDate->format('Y-m-d'),
            'dueDate' => $dueDate->format('Y-m-d'),
            'returnDate' => null,
            'status' => 'BORROWED',
        ];

        $this->loans[$loanId] = $loan;

        return [
            'success' => true,
            'loan' => $loan,
            'book' => $this->books[$bookId],
            'message' => 'Book borrowed successfully',
        ];
    }

    /**
     * Return a book for a user.
     *
     * Mirrors POST /return from the sequence diagram and
     * updates the LOAN entity.
     *
     * @return array<string, mixed>
     */
    public function returnBook(int $userId, int $bookId): array
    {
        $user = $this->users[$userId] ?? null;
        if ($user === null) {
            return [
                'success' => false,
                'statusCode' => 404,
                'error' => 'User not found',
            ];
        }

        $book = $this->books[$bookId] ?? null;
        if ($book === null) {
            return [
                'success' => false,
                'statusCode' => 404,
                'error' => 'Book not found',
            ];
        }

        // Find an active loan for this user and book
        $activeLoanId = null;
        foreach ($this->loans as $id => $loan) {
            if (
                $loan['userId'] === $userId
                && $loan['bookId'] === $bookId
                && ($loan['status'] ?? '') === 'BORROWED'
            ) {
                $activeLoanId = $id;
                break;
            }
        }

        if ($activeLoanId === null) {
            return [
                'success' => false,
                'statusCode' => 409,
                'error' => 'No active loan found for this user and book',
            ];
        }

        $returnDate = new DateTimeImmutable();

        $this->loans[$activeLoanId]['status'] = 'RETURNED';
        $this->loans[$activeLoanId]['returnDate'] = $returnDate->format('Y-m-d');

        // Increase available copies
        $this->books[$bookId]['copies_available']++;
        $this->books[$bookId]['available'] = true;

        return [
            'success' => true,
            'loan' => $this->loans[$activeLoanId],
            'book' => $this->books[$bookId],
            'message' => 'Book returned successfully',
        ];
    }

    /**
     * All loans for a given user.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getLoansForUser(int $userId): array
    {
        return array_values(array_filter(
            $this->loans,
            static fn (array $loan): bool => $loan['userId'] === $userId
        ));
    }

    /**
     * All reservations for a given user.
     *
     * For now, this just returns the in-memory reservation list.
     * It matches the RESERVATION entity and "Place Hold" use case
     * from the Mermaid diagrams.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getReservationsForUser(int $userId): array
    {
        return array_values(array_filter(
            $this->reservations,
            static fn (array $reservation): bool => $reservation['userId'] === $userId
        ));
    }
}

