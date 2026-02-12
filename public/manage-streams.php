<?php

declare(strict_types=1);

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Holo-schedule – Streams toevoegen</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
    <style>
        body {
            min-height: 100vh;
            background: radial-gradient(circle at top, #1e293b, #020617);
            color: #e5e7eb;
        }
        main.container {
            max-width: 1120px;
            margin-top: 2rem;
            margin-bottom: 3rem;
            padding-inline: 1rem;
        }
        header.app-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            gap: 1rem;
        }
        header.app-header h1 {
            margin-bottom: 0.25rem;
        }
        .card {
            border-radius: 1rem;
            padding: 1.5rem;
            background: rgba(15, 23, 42, 0.9);
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(148, 163, 184, 0.3);
            backdrop-filter: blur(16px);
        }
        h1, h2, h3 {
            letter-spacing: 0.02em;
        }
        #user-bar {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        #user-bar button {
            margin-left: 0.75rem;
        }
        #viewer-section .grid {
            align-items: flex-start;
            gap: 1.5rem;
        }
        #add-stream-form,
        #import-youtube-form {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        #add-stream-form button,
        #import-youtube-form button {
            align-self: flex-start;
        }
        .back-link {
            margin-bottom: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.9rem;
        }
        .back-link span {
            font-size: 1.1rem;
            line-height: 1;
        }
        @media (max-width: 768px) {
            header.app-header {
                flex-direction: column;
                align-items: flex-start;
            }
            #viewer-section .grid {
                grid-template-columns: minmax(0, 1fr);
            }
        }
    </style>
</head>
<body>
<main class="container">
    <header class="app-header">
        <div>
            <h1>Holo-schedule</h1>
            <p>Streams toevoegen aan je persoonlijke schema.</p>
        </div>
        <div id="user-bar">
            <span id="user-info">Niet ingelogd</span>
            <button id="logout-btn" class="secondary outline" type="button" hidden>Uitloggen</button>
        </div>
    </header>

    <a href="index.php" class="back-link">
        <span>⟵</span>
        Terug naar schema
    </a>

    <section id="viewer-section" class="card">
        <h2>Streams toevoegen</h2>
        <p style="font-size:0.9rem;opacity:0.9;">
            Plak een YouTube-link en laat Holo-schedule automatisch de titel, het kanaal en tijden invullen. Of importeer geplande streams van een volledig kanaal.
        </p>

        <div class="grid">
            <form id="add-stream-form">
                <h3>Handmatig toevoegen</h3>
                <label>
                    YouTube stream link
                    <input type="url" name="url" placeholder="https://www.youtube.com/watch?v=..." required>
                </label>
                <small>
                    Alleen YouTube-links worden ondersteund. Titel, kanaal, start- en eindtijd worden automatisch opgehaald.
                </small>
                <button type="submit">Stream toevoegen via link</button>
            </form>

            <form id="import-youtube-form">
                <h3>YouTube import</h3>
                <label>
                    YouTube channel ID
                    <input type="text" name="channel_id" placeholder="bijv. UC... (channel ID)" required>
                </label>
                <small>
                    Holo-schedule haalt geplande livestreams voor dit kanaal op met de YouTube API en voegt ze toe aan jouw schema.
                </small>
                <button type="submit">YouTube-streams importeren</button>
            </form>
        </div>
    </section>
</main>

<script src="app.js"></script>
</body>
</html>

