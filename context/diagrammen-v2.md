# Updated Architecture Diagrams (V2) - Current Implementation

## Applicatie-overzicht (high-level) - Current Tech Stack

```mermaid
flowchart LR
    subgraph Users
        V[Viewer]
        S[Streamer]
    end

    subgraph Frontend
        UI[Vanilla JS UI]
    end

    subgraph Backend
        API[PHP API - api.php]
    end

    subgraph Data
        DB[(SQLite Database)]
    end

    subgraph External
        YT[YouTube Data API]
    end

    V -->|Bekijkt schema, beheert streams| UI
    S -->|Beheert streams| UI

    UI -->|JSON requests| API
    API --> DB
    API --> YT

    style UI fill:#f9f,stroke:#333
    style API fill:#bbf,stroke:#333
    style DB fill:#9f9,stroke:#333
```

## Datastroom / API-flow - Current Implementation

```mermaid
sequenceDiagram
    actor User as User (Viewer/Streamer)
    participant UI as Web UI (Vanilla JS)
    participant API as PHP API (api.php)
    participant DB as SQLite DB
    participant YT as YouTube API

    %% User registers/logs in
    User->>UI: Register/Login
    UI->>API: POST /register or /login
    API->>DB: Store/retrieve user
    DB-->>API: User data
    API-->>UI: Session + user info

    %% User adds stream manually
    User->>UI: Enter stream URL
    UI->>API: POST /add_stream_manual {url}
    API->>YT: Validate YouTube link & fetch metadata
    YT-->>API: Video metadata
    API->>DB: INSERT stream with metadata
    DB-->>API: Confirmation
    API-->>UI: Success response

    %% User imports YouTube streams
    User->>UI: Enter channel ID
    UI->>API: POST /import_youtube_streams {channel_id}
    API->>YT: Search upcoming streams
    YT-->>API: Stream list
    API->>YT: Get stream details
    YT-->>API: Detailed metadata
    API->>DB: INSERT multiple streams
    DB-->>API: Confirmation
    API-->>UI: Import count

    %% User views schedule
    UI->>API: GET /my_schedule
    API->>DB: SELECT streams for user
    DB-->>API: Stream data
    API-->>UI: Stream list (JSON)

    %% Logout
    User->>UI: Click logout
    UI->>API: POST /logout
    API->>API: Destroy session
    API-->>UI: Success
```

## Database Schema - Current Implementation

```mermaid
classDiagram
    class users {
        +id INTEGER PRIMARY KEY
        +email TEXT UNIQUE
        +password_hash TEXT
        +role TEXT (viewer/streamer)
        +display_name TEXT
        +timezone TEXT
        +created_at TEXT
    }

    class streams {
        +id INTEGER PRIMARY KEY
        +streamer_id INTEGER FK
        +title TEXT
        +description TEXT
        +platform TEXT
        +url TEXT
        +start_time_utc TEXT
        +category TEXT
        +is_recurring INTEGER
        +recurrence_rule TEXT
        +created_at TEXT
    }

    class follows {
        +viewer_id INTEGER FK
        +streamer_id INTEGER FK
        +created_at TEXT
    }

    users "1" -- "0..*" streams : creates
    users "1" -- "0..*" follows : viewer
    users "1" -- "0..*" follows : streamer
```

## Key Differences from Original Diagrams

1. **Simplified Tech Stack**:
   - Vanilla JavaScript instead of React/TypeScript
   - PHP backend instead of Node.js/Express
   - SQLite instead of PostgreSQL
   - Session-based auth instead of JWT

2. **Monolithic Architecture**:
   - Single `api.php` file handles all API endpoints
   - No separate services (Auth, Schedule, Notifications, Integrations)
   - No background jobs or notification system

3. **Implemented Features**:
   - ✅ User authentication (register/login/logout)
   - ✅ Stream creation with YouTube validation
   - ✅ Schedule viewing
   - ✅ Follow relationships
   - ❌ No notification system
   - ❌ No email provider integration
   - ❌ No background processing

4. **Current Flow**:
   - All requests go through single PHP endpoint
   - Direct SQLite queries (no ORM)
   - YouTube API integration for stream validation
   - Session-based authentication
   - Simple JSON responses to frontend