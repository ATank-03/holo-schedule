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
        section#viewer-section h3 {
            margin-bottom: 0.75rem;
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
        .schedule-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-top: 2rem;
            margin-bottom: 0.75rem;
        }
        .schedule-header h3 {
            margin: 0;
        }
        .schedule-actions {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .schedule-table-shell {
            border-radius: 0.9rem;
            border: 1px solid rgba(148, 163, 184, 0.3);
            background: radial-gradient(circle at top left, rgba(15, 23, 42, 0.85), rgba(15, 23, 42, 0.95));
            overflow: hidden;
        }
        .schedule-table-scroll {
            max-height: 840px;
            overflow: auto;
        }
        table {
            width: 100%;
            font-size: 0.9rem;
            border-collapse: collapse;
            table-layout: fixed;
        }
        #schedule-table thead {
            background: rgba(15, 23, 42, 0.96);
        }
        #schedule-table th,
        #schedule-table td {
            padding: 0.55rem 0.75rem;
            border-bottom: 1px solid rgba(31, 41, 55, 0.7);
            white-space: nowrap;
        }
        #schedule-table tbody tr:nth-child(even) {
            background-color: rgba(15, 23, 42, 0.7);
        }
        #schedule-table td:nth-child(2),
        #schedule-table td:nth-child(3),
        #schedule-table td:nth-child(4),
        #schedule-table td:nth-child(5),
        #schedule-table td:nth-child(6) {
            white-space: normal;
            word-break: break-word;
        }
        #schedule-table td:last-child a {
            word-break: break-all;
        }
        @media (max-width: 768px) {
            header.app-header {
                flex-direction: column;
                align-items: flex-start;
            }
            #auth-section .grid,
            #viewer-section .grid {
                grid-template-columns: minmax(0, 1fr);
            }
            .schedule-header {
                flex-direction: column;
                align-items: flex-start;
            }
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

        <div class="schedule-header">
            <h3>Mijn schema</h3>
            <div class="schedule-actions">
                <a href="manage-streams.php" role="button" class="secondary">Streams toevoegen</a>
                <button id="refresh-schedule">Ververs schema</button>
            </div>
        </div>
        <div class="schedule-table-shell">
            <div class="schedule-table-scroll">
                <table id="schedule-table">
                    <thead>
                    <tr>
                        <th>Datum (UTC)</th>
                        <th>Kanaal</th>
                        <th>Titel</th>
                        <th>Start (UTC)</th>
                        <th>Uren tot start</th>
                        <th>Platform</th>
                        <th>Link</th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </section>
</main>

<script src="app.js"></script>
</body>
</html>

