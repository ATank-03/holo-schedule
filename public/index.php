<?php

declare(strict_types=1);

// Simple front controller serving the main HTML shell.

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Holo-schedule</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
    <style>
        body {
            min-height: 100vh;
            background: radial-gradient(circle at top, #1e293b, #020617);
            color: #e5e7eb;
        }
        main.container {
            max-width: 900px;
            margin-top: 2rem;
            margin-bottom: 3rem;
        }
        header.app-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
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
        }
        #auth-section .grid {
            gap: 2rem;
        }
        #auth-section h2 {
            margin-bottom: 1rem;
        }
        #auth-status {
            margin-top: 1rem;
        }
        #user-bar {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        #user-bar button {
            margin-left: 0.75rem;
        }
        table {
            font-size: 0.9rem;
        }
        #schedule-table tbody tr:nth-child(even) {
            background-color: rgba(15, 23, 42, 0.7);
        }
        .schedule-stream {
            display: block;
            margin-bottom: 0.25rem;
        }
        .schedule-stream small {
            opacity: 0.8;
        }
    </style>
</head>
<body>
<main class="container">
    <header class="app-header">
        <div>
            <h1>Holo-schedule</h1>
            <p>Plan en bekijk livestreams van je favoriete streamers.</p>
        </div>
        <div id="user-bar">
            <span id="user-info">Niet ingelogd</span>
            <button id="logout-btn" class="secondary outline" type="button" hidden>Uitloggen</button>
        </div>
    </header>

    <section id="auth-section" class="card">
        <h2>Account</h2>
        <p>Maak een account aan of log in om je schema te beheren.</p>
        <div class="grid">
            <form id="register-form" class="card">
                <h3>Registreren</h3>
                <p style="font-size:0.9rem;opacity:0.85;">
                    Accounts zijn altijd <strong>viewers</strong>. Gebruik je account om een persoonlijk kijk-schema te bouwen.
                </p>
                <label>
                    E-mail
                    <input type="email" name="email" required>
                </label>
                <label>
                    Wachtwoord
                    <input type="password" name="password" required>
                </label>
                <label>
                    Display naam
                    <input type="text" name="display_name" required>
                </label>
                <button type="submit">Registreren</button>
            </form>

            <form id="login-form" class="card">
                <h3>Inloggen</h3>
                <label>
                    E-mail
                    <input type="email" name="email" required>
                </label>
                <label>
                    Wachtwoord
                    <input type="password" name="password" required>
                </label>
                <button type="submit">Inloggen</button>
            </form>
        </div>
        <p id="auth-status"></p>
    </section>

    <section id="viewer-section" class="card" hidden>
        <h2>Mijn kijk-schema</h2>

        <h3>Streams toevoegen</h3>
        <div class="grid">
            <form id="add-stream-form">
                <label>
                    Titel
                    <input type="text" name="title" required>
                </label>
                <label>
                    Kanaal / streamer naam
                    <input type="text" name="channel_name" placeholder="bijv. \"Mori Calliope\"" required>
                </label>
                <label>
                    Platform
                    <select name="platform" required>
                        <option value="YouTube">YouTube</option>
                        <option value="Twitch">Twitch</option>
                        <option value="Other">Other</option>
                    </select>
                </label>
                <label>
                    Stream link (URL)
                    <input type="url" name="url" placeholder="https://www.youtube.com/watch?v=..." required>
                </label>
                <label>
                    Starttijd (UTC)
                    <input type="datetime-local" name="start_time" required>
                </label>
                <label>
                    Eindtijd (UTC)
                    <input type="datetime-local" name="end_time" required>
                </label>
                <button type="submit">Stream handmatig toevoegen</button>
            </form>

            <form id="import-youtube-form">
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

        <h3 style="margin-top:2rem;">Mijn schema</h3>
        <button id="refresh-schedule">Ververs schema</button>
        <table id="schedule-table">
            <thead>
            <tr>
                <th>Datum (UTC)</th>
                <th>Kanaal</th>
                <th>Titel</th>
                <th>Start (UTC)</th>
                <th>Eind (UTC)</th>
                <th>Platform</th>
                <th>Link</th>
            </tr>
            </thead>
            <tbody></tbody>
        </table>
    </section>
</main>

<script src="app.js"></script>
</body>
</html>

