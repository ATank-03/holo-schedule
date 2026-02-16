### Wat ik heb toegevoegd

- **`import.sql`** (in de projectroot)
  - Maakt de database **`library_api`** aan (utf8mb4).
  - Maakt tabellen die de ERD volgen:
    - `libraries` (`library_id`, `name`, `address`, `phone`, `email`)
    - `users` (als `MEMBER`: `member_id`, `name`, `email`, `phone`, `membership_date`, `membership_status`, `library_id` + FK naar `libraries`)
    - `authors` (`author_id`, `name`, `biography`)
    - `categories` (`category_id`, `category_name`, `description`)
    - `books` (`book_id`, `title`, `isbn`, `author_id`, `category_id`, `library_id`, `description`, `copies_available`, `total_copies` + FKs)
    - `loans` (`loan_id`, `member_id`, `book_id`, `loan_date`, `due_date`, `return_date`, `status` ENUM)
    - `reservations` (`reservation_id`, `member_id`, `book_id`, `reservation_date`, `status` ENUM)
  - Alle relaties en foreign keys zijn aangemaakt volgens het `library-ERD.mmd` diagram.

- **`seeder.php`** (in de projectroot)
  - CLI‑script dat via **PDO** verbinding maakt met MySQL/MariaDB (`library_api` DB).
  - Doet:
    - Foreign keys tijdelijk uit, truncate van `reservations`, `loans`, `books`, `categories`, `authors`, `users`, `libraries`.
    - Voegt 1 bibliotheek toe: `Central Library`.
    - Voegt 2 users/members toe: `Alice Reader`, `Bob Borrower` (status `ACTIVE`).
    - Voegt 3 authors toe: Orwell, Huxley, Tolkien.
    - Voegt 3 categories toe: Dystopian, Science Fiction, Fantasy.
    - Voegt 3 boeken toe: *1984*, *Brave New World*, *The Hobbit* met dezelfde beschrijvingen en aantallen als in `LibraryService`.
  - Run zo:
    ```bash
    # eerst import.sql importeren (bijv. via mysql client of phpMyAdmin)
    mysql -u root -p < import.sql

    # daarna data seeden
    php seeder.php
    ```
  - Aan het begin van `seeder.php` kun je host, poort, user en wachtwoord aanpassen aan jouw MariaDB/MySQL installatie.