## PHP Library REST API

Simple, framework-less REST API in PHP for a library system, based on the Mermaid diagrams in the `mermaid-code` directory:

- **`library-ERD.mmd`**: entities like `BOOK`, `MEMBER`/`USER`, `LOAN`, `RESERVATION`, `CATEGORY`, `AUTHOR`
- **`class-diagram.mmd`**: domain classes such as `User`, `Book`, `Loan`, `Reservation`, `Category`
- **`use-case-diagram.mmd`**: use cases like "Search Books", "Borrow Book", "Return Book"
- **`sequence-diagram.mmd`**: API flow for searching books, borrowing a book, and returning a book

This project mirrors those diagrams with a small in-memory PHP API.

### Structure

- **`index.php`**: Main HTTP entrypoint and very small router.
- **`src/LibraryService.php`**: In-memory "domain service" with users, books, categories, loans, and reservations.
- **`mermaid-code/`**: Design diagrams the API is based on.

### Running the API

You only need PHP installed; no database or framework is required.

From the project root:

```bash
php -S localhost:8000
```

This will serve `index.php` on `http://localhost:8000`.

### Available endpoints

All responses are JSON.

- **Health check**
  - **GET** `/` or `/health`
  - **200 OK**, body:
    - `{ "status": "ok", "service": "library-api" }`

- **Search or list books** (maps to "Search Books" and "Browse Catalog" use cases and `/books?query=...` from the sequence diagram)
  - **GET** `/books`
  - **Query params**:
    - `query` (optional) – search term for title/author (e.g. `?query=1984`)
  - **200 OK**, body: array of book objects

- **Get book details** (maps to "View Book Details")
  - **GET** `/books/{id}`
  - **200 OK** with book JSON, or **404** if not found

- **List categories**
  - **GET** `/categories`
  - **200 OK** with array of `{ id, name }`

- **Books by category**
  - **GET** `/categories/{id}/books`
  - **200 OK** with array of books in the category (possibly empty)

- **Borrow a book** (maps to "Borrow Book" and `POST /borrow` from the sequence diagram, and `LOAN` from the ERD)
  - **POST** `/borrow`
  - **Body (JSON)**:
    - `{ "userId": 1, "bookId": 1 }`
  - **Success (200 OK)**:
    - `{ "success": true, "loan": {...}, "book": {...}, "message": "Book borrowed successfully" }`
  - **Error examples**:
    - **404**: user or book not found
    - **409**: no copies available, or user not allowed to borrow

- **Return a book** (maps to "Return Book" and `POST /return` from the sequence diagram)
  - **POST** `/return`
  - **Body (JSON)**:
    - `{ "userId": 1, "bookId": 1 }`
  - **Success (200 OK)**:
    - `{ "success": true, "loan": {...}, "book": {...}, "message": "Book returned successfully" }`
  - **Error examples**:
    - **404**: user or book not found
    - **409**: no active loan for that user/book

- **User loans** (maps to `Loan` relations on the class diagram)
  - **GET** `/users/{id}/loans`
  - **200 OK** with array of loans for the user (may be empty)

- **User reservations** (maps to `Reservation` relations on the class diagram and "Place Hold" use case)
  - **GET** `/users/{id}/reservations`
  - **200 OK** with array of reservations for the user (empty in the default seed data)

### Notes

- Data is **in-memory only** and is re-created on every request; there is no database.
- The field names and relationships are inspired by the Mermaid ERD and class diagrams, but simplified for clarity.
- This structure can later be swapped for a real database and richer domain objects without changing the endpoints.

