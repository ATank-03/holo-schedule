## Library REST API – Specificatie

Deze specificatie beschrijft de REST API van de bibliotheek‑service, zoals geïmplementeerd in `index.php` en `src/LibraryService.php`.

Alle responses zijn in **JSON**.  
Standaard headers:

- `Content-Type: application/json`
- CORS: `Access-Control-Allow-Origin: *`

De API gebruikt (in deze oefenversie) **in‑memory data**, dus data wordt niet persisterend opgeslagen.

---

## Overzicht endpoints

- **Health**
  - `GET /`
  - `GET /health`
- **Boeken**
  - `GET /books`
  - `GET /books/{id}`
- **Categorieën**
  - `GET /categories`
  - `GET /categories/{id}/books`
- **Lenen & terugbrengen**
  - `POST /borrow`
  - `POST /return`
- **Gebruiker‑overzichten**
  - `GET /users/{id}/loans`
  - `GET /users/{id}/reservations`

---

## 1. Health check

### 1.1 GET `/` of `/health`

Controleert of de API draait.

**Request**

- Method: `GET`
- Body: *geen*

**Response 200 OK**

```json
{
  "status": "ok",
  "service": "library-api"
}
```

---

## 2. Boeken

### 2.1 GET `/books`

Zoekt naar boeken op basis van een zoekstring, of geeft alle boeken terug als er geen zoekterm is.

**Request**

- Method: `GET`
- Query parameters:
  - `query` *(optioneel, string)*  
    Zoekterm; wordt vergeleken met de **titel (name)** en **beschrijving** van een boek (case‑insensitive).

**Voorbeeld**

- `GET /books`
- `GET /books?query=hobbit`

**Response 200 OK**

Body: array van boek‑objecten.

```json
[
  {
    "id": 3,
    "title": "The Hobbit",
    "author": "J.R.R. Tolkien",
    "description": "A fantasy adventure following Bilbo Baggins on a journey to reclaim a lost dwarf kingdom.",
    "isbn": "9780547928227",
    "available": true,
    "categories": [
      { "id": 3, "name": "Fantasy" }
    ],
    "copies_available": 2,
    "total_copies": 2
  }
]
```

### 2.2 GET `/books/{id}`

Haalt details op van één specifiek boek.

**Request**

- Method: `GET`
- Padparameters:
  - `id` *(integer)* – id van het boek.

**Response 200 OK**

Body: boek‑object (zelfde structuur als hierboven).

**Response 404 Not Found**

```json
{
  "error": "Book not found"
}
```

---

## 3. Categorieën

### 3.1 GET `/categories`

Geeft alle categorieën terug.

**Request**

- Method: `GET`

**Response 200 OK**

```json
[
  { "id": 1, "name": "Dystopian" },
  { "id": 2, "name": "Science Fiction" },
  { "id": 3, "name": "Fantasy" }
]
```

### 3.2 GET `/categories/{id}/books`

Geeft alle boeken in een bepaalde categorie.

**Request**

- Method: `GET`
- Padparameters:
  - `id` *(integer)* – id van de categorie.

**Response 200 OK**

Body: array van boeken die aan deze categorie gekoppeld zijn (kan leeg zijn).

```json
[
  {
    "id": 1,
    "title": "1984",
    "author": "George Orwell",
    "description": "A dystopian novel about a totalitarian regime that controls every aspect of life.",
    "isbn": "9780451524935",
    "available": true,
    "categories": [
      { "id": 1, "name": "Dystopian" },
      { "id": 2, "name": "Science Fiction" }
    ],
    "copies_available": 3,
    "total_copies": 3
  }
]
```

---

## 4. Lenen & terugbrengen

### 4.1 POST `/borrow`

Leent een boek uit aan een gebruiker.  
Maakt een nieuwe **loan** aan als er exemplaren beschikbaar zijn.

**Request**

- Method: `POST`
- Headers:
  - `Content-Type: application/json`
- Body:

```json
{
  "userId": 1,
  "bookId": 1
}
```

**Response 200 OK – succes**

```json
{
  "success": true,
  "loan": {
    "id": 1,
    "userId": 1,
    "bookId": 1,
    "loanDate": "2026-02-10",
    "dueDate": "2026-02-24",
    "returnDate": null,
    "status": "BORROWED"
  },
  "book": {
    "id": 1,
    "title": "1984",
    "author": "George Orwell",
    "description": "A dystopian novel about a totalitarian regime that controls every aspect of life.",
    "isbn": "9780451524935",
    "available": true,
    "categories": [
      { "id": 1, "name": "Dystopian" },
      { "id": 2, "name": "Science Fiction" }
    ],
    "copies_available": 2,
    "total_copies": 3
  },
  "message": "Book borrowed successfully"
}
```

**Mogelijke fout‑responses**

- **400 Bad Request** – ontbrekende velden

```json
{ "error": "userId and bookId are required" }
```

- **404 Not Found** – gebruiker of boek bestaat niet

```json
{ "success": false, "statusCode": 404, "error": "User not found" }
```

of

```json
{ "success": false, "statusCode": 404, "error": "Book not found" }
```

- **409 Conflict** – domeinconflict

Voorbeelden:

```json
{ "success": false, "statusCode": 409, "error": "User is not allowed to borrow books" }
```

```json
{ "success": false, "statusCode": 409, "error": "No copies available" }
```

### 4.2 POST `/return`

Markeert een geleend boek als teruggebracht.

**Request**

- Method: `POST`
- Headers:
  - `Content-Type: application/json`
- Body:

```json
{
  "userId": 1,
  "bookId": 1
}
```

**Response 200 OK – succes**

```json
{
  "success": true,
  "loan": {
    "id": 1,
    "userId": 1,
    "bookId": 1,
    "loanDate": "2026-02-10",
    "dueDate": "2026-02-24",
    "returnDate": "2026-02-11",
    "status": "RETURNED"
  },
  "book": {
    "id": 1,
    "title": "1984",
    "author": "George Orwell",
    "description": "A dystopian novel about a totalitarian regime that controls every aspect of life.",
    "isbn": "9780451524935",
    "available": true,
    "categories": [
      { "id": 1, "name": "Dystopian" },
      { "id": 2, "name": "Science Fiction" }
    ],
    "copies_available": 3,
    "total_copies": 3
  },
  "message": "Book returned successfully"
}
```

**Mogelijke fout‑responses**

- **400 Bad Request** – ontbrekende velden

```json
{ "error": "userId and bookId are required" }
```

- **404 Not Found** – gebruiker of boek bestaat niet

```json
{ "success": false, "statusCode": 404, "error": "User not found" }
```

```json
{ "success": false, "statusCode": 404, "error": "Book not found" }
```

- **409 Conflict** – geen actieve lening gevonden

```json
{
  "success": false,
  "statusCode": 409,
  "error": "No active loan found for this user and book"
}
```

---

## 5. Gebruiker‑overzichten

### 5.1 GET `/users/{id}/loans`

Haalt alle leningen op voor een specifieke gebruiker.

**Request**

- Method: `GET`
- Padparameters:
  - `id` *(integer)* – id van de gebruiker.

**Response 200 OK**

Body: array van loan‑objecten (kan leeg zijn).

```json
[
  {
    "id": 1,
    "userId": 1,
    "bookId": 1,
    "loanDate": "2026-02-10",
    "dueDate": "2026-02-24",
    "returnDate": null,
    "status": "BORROWED"
  }
]
```

### 5.2 GET `/users/{id}/reservations`

Haalt alle reserveringen op voor een specifieke gebruiker.

**Request**

- Method: `GET`
- Padparameters:
  - `id` *(integer)* – id van de gebruiker.

**Response 200 OK**

Body: array van reservation‑objecten (in de huidige implementatie standaard leeg).

```json
[]
```

---

## 6. Foutafhandeling algemeen

- **404 Not Found**
  - Als er geen route matcht:

```json
{ "error": "Route not found" }
```

- **500 Internal Server Error**
  - Bij onverwachte fouten in de applicatie:

```json
{
  "error": "Internal server error",
  "message": "Technische foutmelding"
}
```

