### Mogelijke fouten / valkuilen

- **Data verdwijnt elke request**  
  `LibraryService` roept in de constructor altijd `seedData()` aan. Daardoor zijn **alle loans en reserveringen weg bij elke nieuwe HTTP‑request**. Voor demo is dat oké, maar in een echte app is dit een bug/valkuil (je wilt opslag in een database of sessie).

- **Routing breekbaar bij submap**  
  In `index.php` wordt `REQUEST_URI` direct gebruikt en `trim($path, '/')` gedaan. Als je deze API niet op de root (`/`) maar onder een subpad draait (bijv. `/api/index.php/books`), kan de routing snel stukgaan. Een robuustere router zou rekening houden met een base‑path.

- **JSON‑fouten worden niet expliciet gemeld**  
  Bij `POST /borrow` en `POST /return` wordt `json_decode` zonder controle op `json_last_error()` gebruikt. Bij kapotte JSON krijg je nu slechts `"userId and bookId are required"`, wat niet duidelijk maakt dat de body zelf geen geldige JSON was.

- **Geen controle op HTTP‑methods per route**  
  Als iemand bijvoorbeeld `PUT /books` doet, krijgt hij gewoon "Route not found" (404), terwijl een **405 Method Not Allowed** logischer is. Dit maakt debugging van clients minder duidelijk.

- **Geen authenticatie/autorisatie**  
  Iedereen kan borrowers spelen met elk `userId`. Voor de oefening prima, maar functioneel klopt het niet met het idee van `User.login()` / `UserStatus` uit je diagrammen.

- **Timezone / datumnauwkeurigheid**  
  `DateTimeImmutable` gebruikt de **default server timezone**. Dat is vaak goed genoeg, maar kan tot verrassingen leiden bij leningen/terugbrengen in andere tijdzones. In een serieuze app zou je expliciet een timezone kiezen (bijv. UTC).

### Verbeteringen in structuur

- **Scheiding HTTP vs domein**  
  `index.php` kent alle endpoints én alle foutafhandeling. Mooier is een kleine router + controllers, waarbij `LibraryService` puur domeinlogica is en geen HTTP‑details (statuscodes) in zijn return‑arrays heeft.

- **Consistente API‑responses**  
  Nu heb je soms `{success: true, ...}` en soms `{error: '...'}`. Je zou een vast response‑formaat kunnen kiezen, bv. altijd `{ success, data, error }`.

- **Autoloading en mappenstructuur**  
  `composer.json` heeft nog geen `autoload`‑sectie. Je zou PSR‑4 kunnen instellen, zodat je niet zelf `require_once` hoeft te doen en makkelijker meer classes kunt toevoegen.

- **Meer domeinregels**  
  Nu kan dezelfde user hetzelfde boek meerdere keren lenen (zolang er copies zijn). Je zou extra regels kunnen inbouwen (bijv. maximaal één actieve loan per user/boek, of max aantal leningen per user).

Als je wilt, kan ik één van deze verbeteringen (bijv. betere error‑handling of een 405‑afhandeling) direct in de code voor je inbouwen.