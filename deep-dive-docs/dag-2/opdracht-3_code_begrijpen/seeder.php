<?php

declare(strict_types=1);

/**
 * Simple seeder script for the Library REST API database.
 *
 * This script assumes that `import.sql` has already been executed
 * on a MariaDB/MySQL server, so all tables exist in the `library_api`
 * database. It then inserts some initial data (libraries, users,
 * authors, categories, books).
 *
 * Run from the command line:
 *
 *   php seeder.php
 */

// Adjust these values to match your local database setup.
$dbHost = '127.0.0.1';
$dbPort = '3306';
$dbName = 'library_api';
$dbUser = 'root';
$dbPass = '';

$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $dbHost, $dbPort, $dbName);

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, "Connection failed: " . $e->getMessage() . PHP_EOL);
    exit(1);
}

$pdo->beginTransaction();

try {
    // Clear existing data (respecting foreign key order).
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    $pdo->exec('TRUNCATE TABLE reservations');
    $pdo->exec('TRUNCATE TABLE loans');
    $pdo->exec('TRUNCATE TABLE books');
    $pdo->exec('TRUNCATE TABLE categories');
    $pdo->exec('TRUNCATE TABLE authors');
    $pdo->exec('TRUNCATE TABLE users');
    $pdo->exec('TRUNCATE TABLE libraries');
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

    // 1. Library
    $stmt = $pdo->prepare(
        'INSERT INTO libraries (name, address, phone, email)
         VALUES (:name, :address, :phone, :email)'
    );
    $stmt->execute([
        ':name' => 'Central Library',
        ':address' => '123 Library Street',
        ':phone' => '+31 20 123 4567',
        ':email' => 'info@library.local',
    ]);
    $libraryId = (int) $pdo->lastInsertId();

    // 2. Users (members)
    $stmt = $pdo->prepare(
        'INSERT INTO users (name, email, phone, membership_date, membership_status, library_id)
         VALUES (:name, :email, :phone, :membership_date, :membership_status, :library_id)'
    );

    $today = (new DateTimeImmutable())->format('Y-m-d');

    $stmt->execute([
        ':name' => 'Alice Reader',
        ':email' => 'alice@example.com',
        ':phone' => '+31 6 11111111',
        ':membership_date' => $today,
        ':membership_status' => 'ACTIVE',
        ':library_id' => $libraryId,
    ]);
    $aliceId = (int) $pdo->lastInsertId();

    $stmt->execute([
        ':name' => 'Bob Borrower',
        ':email' => 'bob@example.com',
        ':phone' => '+31 6 22222222',
        ':membership_date' => $today,
        ':membership_status' => 'ACTIVE',
        ':library_id' => $libraryId,
    ]);
    $bobId = (int) $pdo->lastInsertId();

    // 3. Authors
    $stmt = $pdo->prepare(
        'INSERT INTO authors (name, biography) VALUES (:name, :biography)'
    );

    $stmt->execute([
        ':name' => 'George Orwell',
        ':biography' => 'English novelist and critic, author of 1984 and Animal Farm.',
    ]);
    $orwellId = (int) $pdo->lastInsertId();

    $stmt->execute([
        ':name' => 'Aldous Huxley',
        ':biography' => 'English writer and philosopher, author of Brave New World.',
    ]);
    $huxleyId = (int) $pdo->lastInsertId();

    $stmt->execute([
        ':name' => 'J.R.R. Tolkien',
        ':biography' => 'English writer, poet and academic, author of The Hobbit and The Lord of the Rings.',
    ]);
    $tolkienId = (int) $pdo->lastInsertId();

    // 4. Categories
    $stmt = $pdo->prepare(
        'INSERT INTO categories (category_name, description)
         VALUES (:name, :description)'
    );

    $stmt->execute([
        ':name' => 'Dystopian',
        ':description' => 'Novels set in an imagined oppressive and controlled society.',
    ]);
    $dystopianId = (int) $pdo->lastInsertId();

    $stmt->execute([
        ':name' => 'Science Fiction',
        ':description' => 'Speculative fiction dealing with futuristic concepts and technology.',
    ]);
    $scifiId = (int) $pdo->lastInsertId();

    $stmt->execute([
        ':name' => 'Fantasy',
        ':description' => 'Stories set in fictional universes, often with magic or mythical creatures.',
    ]);
    $fantasyId = (int) $pdo->lastInsertId();

    // 5. Books
    $stmt = $pdo->prepare(
        'INSERT INTO books
            (title, isbn, author_id, category_id, library_id, description, copies_available, total_copies)
         VALUES
            (:title, :isbn, :author_id, :category_id, :library_id, :description, :copies_available, :total_copies)'
    );

    // 1984
    $stmt->execute([
        ':title' => '1984',
        ':isbn' => '9780451524935',
        ':author_id' => $orwellId,
        ':category_id' => $dystopianId,
        ':library_id' => $libraryId,
        ':description' => 'A dystopian novel about a totalitarian regime that controls every aspect of life.',
        ':copies_available' => 3,
        ':total_copies' => 3,
    ]);

    // Brave New World
    $stmt->execute([
        ':title' => 'Brave New World',
        ':isbn' => '9780060850524',
        ':author_id' => $huxleyId,
        ':category_id' => $dystopianId,
        ':library_id' => $libraryId,
        ':description' => 'A science fiction classic about a future society built on genetic engineering and conditioning.',
        ':copies_available' => 1,
        ':total_copies' => 1,
    ]);

    // The Hobbit
    $stmt->execute([
        ':title' => 'The Hobbit',
        ':isbn' => '9780547928227',
        ':author_id' => $tolkienId,
        ':category_id' => $fantasyId,
        ':library_id' => $libraryId,
        ':description' => 'A fantasy adventure following Bilbo Baggins on a journey to reclaim a lost dwarf kingdom.',
        ':copies_available' => 2,
        ':total_copies' => 2,
    ]);

    // Optional: we could also seed initial loans/reservations here if desired.

    $pdo->commit();

    fwrite(STDOUT, "Seeding completed successfully." . PHP_EOL);
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "Seeding failed: " . $e->getMessage() . PHP_EOL);
    exit(1);
}

