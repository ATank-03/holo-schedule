## Holo-schedule – Design Document

### Tech stack (tools & frameworks)
- **Frontend**: React + TypeScript (SPA), bundler via Vite, styling met Tailwind CSS voor snelle, consistente UI.
- **Backend**: Node.js met Express voor een eenvoudige REST API-laag.
- **Database**: PostgreSQL voor het opslaan van gebruikersaccounts, streamers, streams en notificatie-instellingen.
- **ORM/DB-laag**: Prisma (of vergelijkbare ORM) voor type-safe database toegang.
- **Authenticatie & autorisatie**: JWT-gebaseerde sessies, wachtwoord-hashing (bcrypt) en role-based access (viewer/streamer/admin).
- **Externe services**: YouTube Data API voor het ophalen/valideren van stream-links; e-mailprovider (bijv. SendGrid/Mailgun) voor notificaties.

### Globale architectuur
- **Client (browser / web-app)**  
  - Single Page Application die:
    - De kalenderweergave toont (dag/week/maand) met streams per streamer.
    - Gebruikers laat inloggen/registreren en streamers laat beheren.
    - Viewers laat volgen/ontvolgen, filteren, notificatie-instellingen beheren en eventueel exporteren naar kalender.
  - Communiceert uitsluitend via JSON-REST endpoints met de backend.

- **API / backend-laag**
  - Verantwoordelijk voor:
    - CRUD-operaties voor streams (aanmaken, bewerken, annuleren, terugkijken).
    - Beheer van gebruikersaccounts (streamers en viewers) en volg-relaties.
    - Business rules zoals overlap-detectie voor streams en basis-tijdzoneconversie.
    - Integratie met de YouTube API (validatie van stream-URL’s, eventueel metadata ophalen).
    - Notificatie-planning (bijv. jobs die 15 minuten voor start notificaties uitsturen).
  - Scheiding in modules:
    - `auth` (login/registratie, JWT),
    - `schedule` (streams, recurring streams, kalender),
    - `notifications` (e-mail/push),
    - `integrations` (YouTube API).

- **Data-opslag**
  - Tabellen/collections voor:
    - `users` (rollen, tijdzone, voorkeuren),
    - `streamers`/`profiles`,
    - `streams` (starttijd, eindtijd, platform, link, categorie),
    - `follows` (viewer ↔ streamer),
    - `notifications` (type, status, geplande tijd).
  - Indexen op streamer, starttijd en viewer-id voor snelle queries van agenda’s.

- **Background processing**
  - Een eenvoudige job-runner of cron-achtige taak:
    - Controleert welke streams binnenkort starten.
    - Stuurt notificaties op basis van ingestelde voorkeuren.

### Belangrijke keuzes
- **SPA met REST API**: gekozen voor een duidelijke scheiding tussen frontend en backend, zodat de UI snel en responsief is en de API later ook door andere clients (bijv. mobile app) gebruikt kan worden.
- **Relationele database (PostgreSQL)**: omdat relaties tussen gebruikers, streamers, streams en follows duidelijk gestructureerd zijn en queries rond agenda’s goed met SQL te modelleren zijn.
- **Basale tijdzone-ondersteuning**: alle tijden intern in UTC opslaan, weergave per gebruiker converteren naar lokale tijdzone; voorkomt veel complexiteit in de logica.
- **Notificaties als async proces**: notificaties niet “on the fly” bij requests versturen, maar via geplande jobs om performance en betrouwbaarheid te verbeteren.
- **YouTube API als eerste integratie**: andere platformen (Twitch, etc.) worden optioneel/uitbreidbaar gehouden door een abstractielaag voor “stream platforms”.

### Bekende risico’s
- **Complexiteit tijdzones & zomer-/wintertijd**: verkeerde conversies kunnen ervoor zorgen dat streams op de verkeerde tijd getoond of genotificeerd worden; vereist goede testcases en duidelijke interne representatie (UTC).
- **Afhankelijkheid en quota van YouTube API**: quota-limieten of API-wijzigingen kunnen functionaliteit breken; mitigatie via caching, foutafhandeling en een duidelijke fallback (handmatige invoer).
- **Notificatiebetrouwbaarheid**: e-mail/SMS/push leunen op externe providers; fouten of vertragingen kunnen leiden tot gemiste streams. Monitoring en logging van notificatie-jobs is noodzakelijk.
- **Scope creep**: user stories bevatten veel “nice to have” features (filters, export, verleden streams, recurring streams). Zonder duidelijke fasering kan de eerste release te groot worden; mitigeren door een MVP-scope te definiëren (must-haves eerst).
- **Beveiliging van accounts en data**: slechte implementatie van auth of onvoldoende validatie kan leiden tot datalekken of misbruik (bijv. ongeautoriseerd bewerken van schema’s); mitigeren via best practices (hashing, inputvalidatie, minimale rechten).

