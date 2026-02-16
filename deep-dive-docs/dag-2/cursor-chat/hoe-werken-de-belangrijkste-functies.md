Ik pak de echt belangrijkste samenhangende stukken eruit: zoeken, lenen, terugbrengen en de koppeling met de routes.

### 1. `searchBooks($query)`

- **Doel**: boeken zoeken op titel of auteur (of alles geven als je niks zoekt).
- **Werking stap‑voor‑stap**:
  1. Als `\$query === ''` → gewoon **alle boeken** teruggeven.
  2. Anders:
     - Zet de zoekterm naar lowercase.
     - Loop door alle boeken.
     - Maak van `title + author` één string, ook lowercase.
     - Check: `str_contains(haystack, queryLower)`.
     - Alleen die boeken die de zoekterm bevatten komen in het resultaat.
- **Route**: aangeroepen door `index.php` bij **GET `/books?query=...`**

### 2. `borrowBook($userId, $bookId)`

- **Doel**: een boek uitlenen aan een user en een nieuwe loan aanmaken.
- **Belangrijkste checks**:
  1. **Bestaat de user?** Zo niet → `success: false`, HTTP 404.
  2. **Is de user actief?** (`status === 'ACTIVE'`)  
     - Zo niet → `success: false`, HTTP 409 (conflict).
  3. **Bestaat het boek?** Zo niet → `success: false`, HTTP 404.
  4. **Zijn er nog exemplaren vrij?** (`copies_available > 0`)  
     - Zo niet → `success: false`, HTTP 409 (“No copies available”).

- **Als alle checks goed zijn**:
  - `copies_available--` en indien nodig `available` op `false` zetten.
  - Nieuwe `loan` object maken:
    - `id` = aantal loans + 1,
    - `userId`, `bookId`,
    - `loanDate` = vandaag,
    - `dueDate` = vandaag + 14 dagen,
    - `returnDate` = `null`,
    - `status` = `"BORROWED"`.
  - Loan in `\$this->loans` opslaan.
  - Resultaat teruggeven met:
    - `success: true`,
    - `loan`: de nieuwe loan,
    - `book`: geüpdatete boek (met 1 exemplaar minder),
    - `message`: tekst.

- **Route**: aangeroepen door `index.php` bij **POST `/borrow`** met JSON `{ "userId": ..., "bookId": ... }`.

### 3. `returnBook($userId, $bookId)`

- **Doel**: een geleend boek terugbrengen en de bestaande loan afronden.
- **Checks**:
  1. **User bestaat?** Anders 404.
  2. **Boek bestaat?** Anders 404.
  3. Zoek in alle loans naar **één actieve loan**:
     - Zelfde `userId`,
     - Zelfde `bookId`,
     - `status === 'BORROWED'`.
     - Geen match → `success: false`, HTTP 409 (“No active loan found...”).
- **Als er een actieve loan is**:
  - Zet `status` op `"RETURNED"`.
  - Zet `returnDate` op vandaag.
  - Verhoog `copies_available` van het boek met 1.
  - Zet `available` weer op `true`.
  - Resultaat teruggeven met:
    - `success: true`,
    - geüpdatete `loan` en `book`,
    - `message`.

- **Route**: aangeroepen bij **POST `/return`** met JSON `{ "userId": ..., "bookId": ... }`.

### 4. Overige belangrijke helpers

- **`getBooksByCategory($categoryId)`**  
  - Loopt door alle boeken en kijkt of één van de `categories` de gevraagde `id` heeft.  
  - Route: **GET `/categories/{id}/books`**.

- **`getLoansForUser($userId)`**  
  - Filtert alle loans op `loan['userId'] === $userId`.  
  - Route: **GET `/users/{id}/loans`**.

- **`getReservationsForUser($userId)`**  
  - Zelfde idee maar dan voor `reservations` (nu nog leeg in seed‑data).  
  - Route: **GET `/users/{id}/reservations`**.

Als je wilt kan ik van één van deze functies een kleine “pseudo‑code” versie maken zodat je de logica nog beter ziet, of oefenen met: “wat zou er gebeuren als ik deze request stuur…?”.