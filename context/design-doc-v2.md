# Holo-schedule – Updated Design Document (V2)

## Current Tech Stack (As Implemented)

### Frontend
- **Language**: Vanilla JavaScript (ES6+)
- **Framework**: None (simple DOM manipulation)
- **Styling**: Pico CSS framework + custom CSS
- **Build**: No bundler (direct browser execution)
- **Features**:
  - Simple form handling
  - Basic DOM rendering
  - Session management
  - JSON API communication

### Backend
- **Language**: PHP 8.1+
- **Framework**: None (custom routing in api.php)
- **Architecture**: Monolithic single-file API
- **Authentication**: Session-based (PHP sessions)
- **API Style**: JSON over HTTP with simple action-based routing
- **Features**:
  - User registration and login
  - Stream management
  - YouTube API integration
  - Schedule retrieval

### Database
- **System**: SQLite 3
- **Access**: PDO (PHP Data Objects)
- **Schema**: Simple relational tables
- **Migrations**: Basic PHP migration script
- **Features**:
  - Users with roles (viewer/streamer)
  - Streams with metadata
  - Follow relationships

### External Services
- **YouTube Data API**: For stream validation and metadata
- **No email provider**: Notifications not implemented
- **No background jobs**: All processing is synchronous

## Current Architecture

### Client (Browser)
- Traditional multi-page application with some AJAX:
  - `index.php` - Main interface with auth and schedule viewing
  - `manage-streams.php` - Stream management interface
  - `app.js` - Client-side JavaScript for dynamic features
- Communicates with backend via JSON API calls
- Basic error handling and UI updates

### API / Backend Layer
- Single entry point: `public/api.php`
- Action-based routing via `?action=` parameter
- Implemented actions:
  - `register`, `login`, `me`, `logout` - Authentication
  - `my_schedule` - Get user's schedule
  - `add_stream_manual` - Add single stream with YouTube validation
  - `import_youtube_streams` - Import multiple streams from channel
- Direct SQLite queries (no ORM)
- YouTube API integration for validation and metadata

### Data Storage
- **SQLite database** (`storage/holo_schedule.sqlite`)
- Three main tables:
  - `users`: Stores user accounts with roles
  - `streams`: Stores stream information with streamer relationships
  - `follows`: Stores viewer-streamer relationships
- Simple indexes for basic performance
- No complex queries or advanced features

## Implemented Features

### ✅ Authentication System
- User registration with email/password
- Session-based login/logout
- Role-based access (viewer/streamer)
- Password hashing with PHP's `password_hash()`

### ✅ Stream Management
- Manual stream addition with URL
- YouTube URL validation and metadata extraction
- Channel-based stream import
- Stream storage with start/end times
- Basic schedule viewing (30 days ahead)

### ✅ YouTube Integration
- Video ID extraction from URLs
- Metadata fetching (title, description, channel)
- Scheduled start/end time retrieval
- Automatic stream creation from YouTube data

### ✅ User Interface
- Responsive design with Pico CSS
- Authentication forms (register/login)
- Schedule table with sorting
- Stream management interface
- Basic error handling and feedback

## Not Implemented (From Original Design)

### ❌ Notification System
- No email notifications
- No push notifications
- No background job processing
- No scheduled reminders

### ❌ Advanced Features
- No timezone conversion in UI
- No recurring stream support
- No stream filtering or search
- No calendar export functionality
- No past streams archive

### ❌ Service Separation
- No separate auth service
- No dedicated schedule service
- No notification service
- No integration service layer

### ❌ Technical Enhancements
- No TypeScript or React frontend
- No Node.js backend
- No PostgreSQL database
- No ORM or query builder
- No JWT authentication
- No proper background job system

## Current Data Flow

```
User → Browser → Vanilla JS → JSON API → PHP api.php → SQLite
                                      ↓
                                  YouTube API
```

## Strengths of Current Implementation

1. **Simplicity**: Easy to understand and modify
2. **No Complex Dependencies**: Works with basic PHP/JS
3. **Fast Setup**: SQLite requires no server configuration
4. **Functional Core**: Basic features work as intended
5. **Low Resource Usage**: Lightweight stack

## Limitations and Technical Debt

1. **Monolithic Architecture**: Hard to extend with new features
2. **No Background Processing**: Can't do scheduled notifications
3. **Limited Error Handling**: Basic error responses
4. **No Tests**: No automated testing
5. **Basic Security**: Session-based auth without advanced features
6. **No Timezone Support**: All times in UTC, no conversion
7. **Limited Scalability**: SQLite not ideal for high traffic

## Potential Evolution Path

To align with original design, could implement:

1. **Frontend Upgrade**:
   - Migrate to React/TypeScript
   - Add proper state management
   - Implement better routing

2. **Backend Modernization**:
   - Migrate to Node.js/Express
   - Implement proper service separation
   - Add JWT authentication

3. **Database Upgrade**:
   - Migrate to PostgreSQL
   - Add proper indexing
   - Implement connection pooling

4. **Feature Enhancements**:
   - Add notification system
   - Implement background jobs
   - Add timezone support
   - Enable recurring streams

5. **Quality Improvements**:
   - Add automated testing
   - Implement proper logging
   - Add monitoring
   - Improve error handling

## Conclusion

The current implementation provides a **functional minimum viable product** with the core features working:
- User authentication
- Stream management
- Schedule viewing
- YouTube integration

However, it lacks the **advanced architecture** and **additional features** shown in the original design documents. The simplified stack makes it easier to understand and modify but limits scalability and extensibility.