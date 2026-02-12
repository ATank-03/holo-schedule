# Holo-schedule (PHP versie)

Een kleine proof-of-concept web-app om livestream-planningen van streamers te beheren en te bekijken.

## Stack

- **Backend**: PHP 8.1+ met een eenvoudige JSON API (`public/api.php`)
- **Database**: SQLite (bestand in `storage/holo_schedule.sqlite`)
- **Frontend**: Single page UI in `public/index.php` met minimale JavaScript (`public/app.js`) en Pico.css voor styling

> Let op: dit is een MVP gericht op de kernflows (registreren/inloggen, streams aanmaken, streamers volgen en een schema bekijken).

## Installatie & starten

1. **Composer autoload genereren**

   In de projectmap (deze map) in een terminal:

   ```bash
   composer install
   ```

2. **Database aanmaken / migraties draaien**

   ```bash
   php src/migrations.php
   ```

   Dit maakt (indien nodig) het SQLite-bestand `storage/holo_schedule.sqlite` en de tabellen:

   - `users`
   - `streams`
   - `follows`

3. **Development server starten**

   ```bash
   php -S localhost:8000 -t public
   ```

   Open daarna `http://localhost:8000` in je browser.

## Belangrijkste functionaliteit

- **Registreren / inloggen**
  - Gebruikers kunnen zich registreren als `viewer` of `streamer`.
  - Wachtwoorden worden gehasht opgeslagen.

- **Streamer**
  - Kan nieuwe streams toevoegen met titel, beschrijving, platform, link en start-/eindtijd (in UTC).
  - Kan eigen geplande streams in een tabel bekijken.
  - Overlappende streams voor dezelfde streamer worden geblokkeerd.

- **Viewer**
  - Kan een streamer volgen op basis van streamer-ID.
  - Kan zijn/haar schema bekijken met alle toekomstige streams van gevolgde streamers.

## Relatie met design-doc

De oorspronkelijke `design-doc.md` beschrijft een Node/Express-backend met PostgreSQL. In deze implementatie is gekozen voor:

- **PHP + SQLite** om eenvoudiger lokaal te kunnen draaien zonder extra afhankelijkheden.
- Een vergelijkbare logische laag (auth, schedule, eenvoudige notificiestructuur in de database) maar zonder echte e-mail/SMS-notificaties. Deze kunnen later worden toegevoegd met een extra service.

De Mermaid-diagrammen in `diagrammen.md` blijven conceptueel kloppen; vervang in je hoofd de “Node/Express API” door “PHP API (api.php)”.

