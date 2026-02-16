### Uitleg `index.php`

**Hoofdlijnen:**

- Laadt de `LibraryService`-klasse (`require_once ...` en `use App\LibraryService;`).
- Zet HTTP‑headers:
  - `Content-Type: application/json` → alles is JSON.
  - CORS‑headers zodat je vanaf bv. een frontend kunt aanroepen.
- Handelt een **OPTIONS** request direct af (CORS preflight) met status 204.

Daarna:

- Maakt een service aan:  
  `\$service = new LibraryService();`
- Leest het HTTP‑method (`GET`, `POST`, ...) en de URL (`REQUEST_URI`).
- Haalt het pad zonder querystring eruit en splitst in segmenten, bv.  
  `/books/1` → `['books', '1']`.

In de `try { ... }` staan de routes:

- **GET `/` of `/health`** → geeft `{"status":"ok","service":"library-api"}` terug.
- **GET `/books`** (optioneel `?query=...`):
  - Leest `$_GET['query']`.
  - Roept `\$service->searchBooks($query)` aan.
  - Stuurt de lijst boeken terug als JSON.
- **GET `/books/{id}`**:
  - Haalt `id` uit de URL.
  - Roept `getBookById`.
  - Als niks gevonden → 404 met `{"error":"Book not found"}`.
- **GET `/categories`** → `getCategories()`.
- **GET `/categories/{id}/books`** → `getBooksByCategory($categoryId)`.

“Acties”:

- **POST `/borrow`**:
  - Leest JSON‑body (`php://input`) en verwacht `userId` en `bookId`.
  - Als die ontbreken → 400 met foutmelding.
  - Roept `borrowBook(userId, bookId)` aan.
  - Als `success === false` zet hij de HTTP status op de meegegeven `statusCode` (bijv. 404 of 409).
  - Stuurt het hele resultaat als JSON terug.
- **POST `/return`**:
  - Zelfde patroon, maar roept `returnBook(userId, bookId)` aan.

“Overzichten per user”:

- **GET `/users/{id}/loans`** → `getLoansForUser(id)`.
- **GET `/users/{id}/reservations`** → `getReservationsForUser(id)`.

Als geen van de routes matcht:

- 404 met `{"error":"Route not found"}`.

Als er een exception optreedt:

- 500 met `{"error":"Internal server error", "message": ... }`.

---

### Uitleg `src/LibraryService.php`

**Algemeen:**

- Namespace `App`.
- Klasse `LibraryService` beheert **in‑memory data** (geen database):
  - `users`, `books`, `categories`, `loans`, `reservations` als arrays.
- In de constructor wordt `seedData()` aangeroepen om voorbeelddata te vullen.

**`seedData()`**

- Maakt twee voorbeeldgebruikers (`Alice Reader`, `Bob Borrower`) met status `ACTIVE`.
- Maakt drie categorieën: `Dystopian`, `Science Fiction`, `Fantasy`.
- Maakt drie boeken:
  - `1984` (Dystopian + Science Fiction, 3 exemplaren).
  - `Brave New World` (Dystopian, 1 exemplaar).
  - `The Hobbit` (Fantasy, 2 exemplaren).
- Zet beginwaarden voor `loans` en `reservations` op lege arrays.

**Zoeken en ophalen:**

- `searchBooks(string $query): array`
  - Als `query` leeg is → retourneert alle boeken.
  - Anders:
    - Zet `query` naar lowercase.
    - Filtert boeken waarvan `title` + `author` de zoekterm bevat.
- `getBookById(int $id): ?array`
  - Geeft het boek met die id of `null`.

**Categorieën:**

- `getCategories(): array`
  - Alle categorieën (values, dus zonder keys).
- `getBooksByCategory(int $categoryId): array`
  - Filtert boeken die een categorie met die `id` in hun `categories`‑lijst hebben.

**Boek lenen – `borrowBook(int $userId, int $bookId): array`**

Stappen:

1. Controleer of user bestaat:
   - Zo niet → `success: false`, `statusCode: 404`, `"User not found"`.
2. Controleer of user status `ACTIVE` is:
   - Zo niet → `success: false`, `statusCode: 409`, `"User is not allowed to borrow books"`.
3. Controleer of boek bestaat:
   - Zo niet → `success: false`, `statusCode: 404`, `"Book not found"`.
4. Controleer of er nog exemplaren zijn:
   - `copies_available <= 0` → `success: false`, `statusCode: 409`, `"No copies available"`.
5. Als alles oké:
   - Verlaag `copies_available` met 1 en update `available` (true/false).
   - Maak een nieuwe `loan`:
     - `id` = aantal bestaande loans + 1.
     - `loanDate` = vandaag.
     - `dueDate` = vandaag + 14 dagen (`DateInterval('P14D')`).
     - `returnDate` = `null`.
     - `status` = `"BORROWED"`.
   - Sla loan op in `\$this->loans`.
   - Retourneer:
     - `success: true`
     - `loan` (details)
     - `book` (geüpdatete boek)
     - `message: "Book borrowed successfully"`.

**Boek terugbrengen – `returnBook(int $userId, int $bookId): array`**

Stappen:

1. Check of user bestaat; anders 404.
2. Check of boek bestaat; anders 404.
3. Zoek een **actieve** loan:
   - Loop door `\$this->loans` en kijk naar een loan met:
     - `userId === $userId`
     - `bookId === $bookId`
     - `status === 'BORROWED'`
   - Als geen gevonden → `success: false`, `statusCode: 409`, `"No active loan found..."`.
4. Als gevonden:
   - Zet `status` op `"RETURNED"`.
   - Zet `returnDate` op vandaag.
   - Verhoog `copies_available` van het boek.
   - Zet `available` = `true`.
   - Retourneer `success: true` + geüpdatete loan en boek.

**Overzichten per gebruiker:**

- `getLoansForUser(int $userId): array`
  - Filtert alle loans waar `loan['userId'] === $userId`.
- `getReservationsForUser(int $userId): array`
  - Filtert alle reservations met die `userId` (in de seed staan er nu nog geen).

---

Als je wilt kan ik de uitleg nog verder “in diagrammen” gieten (bijv. sequentie‑stapjes voor `borrow` en `return`), of één van de functies heel gedetailleerd regel‑voor‑regel uitleggen.