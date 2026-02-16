<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\LibraryService;

/**
 * Integration tests for the LibraryService.
 *
 * Deze tests controleren de belangrijkste scenario's uit `specificatie.md`,
 * maar dan op serviceniveau (dezelfde logica als de REST endpoints gebruiken).
 */
final class LibraryServiceIntegrationTest extends TestCase
{
    private LibraryService $service;

    protected function setUp(): void
    {
        // Elke test krijgt een verse in-memory dataset.
        $this->service = new LibraryService();
    }

    public function testSearchBooksWithoutQueryReturnsAllBooks(): void
    {
        $results = $this->service->searchBooks('');

        $this->assertIsArray($results);
        $this->assertCount(3, $results, 'Verwacht alle seed-boeken zonder query');
    }

    public function testSearchBooksMatchesTitleOrDescription(): void
    {
        // Zoek op een woord dat alleen in de beschrijving voorkomt.
        $results = $this->service->searchBooks('dystopian');

        $this->assertNotEmpty($results);
        $titles = array_column($results, 'title');

        $this->assertContains('1984', $titles);
    }

    public function testBorrowBookHappyFlowCreatesLoanAndReducesCopies(): void
    {
        $result = $this->service->borrowBook(1, 1);

        $this->assertTrue($result['success'] ?? false);
        $this->assertArrayHasKey('loan', $result);
        $this->assertArrayHasKey('book', $result);

        $loan = $result['loan'];
        $book = $result['book'];

        $this->assertSame(1, $loan['userId']);
        $this->assertSame(1, $loan['bookId']);
        $this->assertSame('BORROWED', $loan['status']);

        // 1984 had 3 exemplaren; na lenen moeten er 2 over zijn.
        $this->assertSame(2, $book['copies_available']);
    }

    public function testBorrowBookFailsForUnknownUser(): void
    {
        $result = $this->service->borrowBook(999, 1);

        $this->assertFalse($result['success'] ?? true);
        $this->assertSame(404, $result['statusCode'] ?? null);
        $this->assertSame('User not found', $result['error'] ?? null);
    }

    public function testBorrowBookFailsForUnknownBook(): void
    {
        $result = $this->service->borrowBook(1, 999);

        $this->assertFalse($result['success'] ?? true);
        $this->assertSame(404, $result['statusCode'] ?? null);
        $this->assertSame('Book not found', $result['error'] ?? null);
    }

    public function testBorrowBookFailsWhenNoCopiesAvailable(): void
    {
        // Boek 2 heeft maar 1 exemplaar in seed-data.
        $first = $this->service->borrowBook(1, 2);
        $this->assertTrue($first['success'] ?? false);

        // Tweede keer lenen moet falen.
        $second = $this->service->borrowBook(2, 2);

        $this->assertFalse($second['success'] ?? true);
        $this->assertSame(409, $second['statusCode'] ?? null);
        $this->assertSame('No copies available', $second['error'] ?? null);
    }

    public function testReturnBookAfterBorrowRestoresCopiesAndUpdatesLoan(): void
    {
        $borrow = $this->service->borrowBook(1, 1);
        $this->assertTrue($borrow['success'] ?? false);

        $return = $this->service->returnBook(1, 1);

        $this->assertTrue($return['success'] ?? false);

        $loan = $return['loan'];
        $book = $return['book'];

        $this->assertSame('RETURNED', $loan['status']);
        $this->assertNotNull($loan['returnDate']);

        // Kopieën moeten terug op het originele aantal (3).
        $this->assertSame(3, $book['copies_available']);
    }

    public function testReturnBookWithoutActiveLoanFails(): void
    {
        $result = $this->service->returnBook(1, 1);

        $this->assertFalse($result['success'] ?? true);
        $this->assertSame(409, $result['statusCode'] ?? null);
        $this->assertSame(
            'No active loan found for this user and book',
            $result['error'] ?? null
        );
    }

    public function testGetLoansForUserReturnsLoansCreatedByBorrow(): void
    {
        $this->service->borrowBook(1, 1);
        $this->service->borrowBook(1, 3);

        $loans = $this->service->getLoansForUser(1);

        $this->assertCount(2, $loans);
        $bookIds = array_column($loans, 'bookId');

        $this->assertContains(1, $bookIds);
        $this->assertContains(3, $bookIds);
    }
}

