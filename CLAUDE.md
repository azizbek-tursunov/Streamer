# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**UniVision** — a self-hosted university camera streaming platform. Manages RTSP cameras, serves live video via HLS/WebRTC, restreams to YouTube Live, tracks people counts via YOLO, and integrates with the HEMIS university management system.

## Architecture

### Docker Compose Services (`docker-compose.yml`)
- **laravel** — PHP 8.2 app container (queue producer)
- **nginx** — Nginx Alpine reverse proxy (ports 80/443). Sits behind a university-managed OpenResty proxy that we cannot configure
- **mediamtx** — RTSP/HLS/WebRTC streaming server with on-demand FFmpeg relay
- **redis** — Queue broker between Laravel and YOLO worker
- **pgsql** — PostgreSQL 17 database
- **yolo** — FastAPI/Python people-counting worker (queue consumer)
- **certbot** — Let's Encrypt SSL renewal

### Tech Stack
- **Backend**: Laravel 12 + Inertia.js v2 + Fortify (auth)
- **Frontend**: Vue 3 + TypeScript + Tailwind CSS 4 + shadcn-vue (radix-vue/reka-ui)
- **Streaming**: MediaMTX + FFmpeg (RTSP → HLS/WebRTC, `-c:a libopus` for WebRTC audio)
- **Permissions**: Spatie Laravel Permission (roles & permissions)
- **HEMIS Integration**: OAuth login + API sync for users, faculties, auditoriums, lesson schedules

### Key Directories
```
laravel/                    # Laravel application root
├── app/
│   ├── Http/Controllers/   # Route controllers
│   ├── Models/             # Eloquent models (Camera, User, Faculty, etc.)
│   ├── Models/Hemis/       # Hemis-specific models (Auditorium)
│   ├── Services/           # MediaMtxService (API client for MediaMTX)
│   ├── Imports/            # Excel/CSV imports (CamerasImport)
│   └── Console/Commands/   # Artisan commands
├── resources/js/
│   ├── pages/              # Vue pages (Cameras/, Auditoriums/, Dashboard, etc.)
│   ├── components/         # Reusable Vue components (VideoPlayer, CameraDialog, etc.)
│   ├── composables/        # Vue composables (usePermissions)
│   └── layouts/            # AppLayout
├── routes/web.php          # All web routes
├── routes/settings.php     # Settings-area routes (profile, password, sessions, 2FA)
├── mediamtx.yml.template   # MediaMTX config template (env vars substituted at container start)
└── docker/
    ├── nginx/default.conf.template  # Nginx config template (MEDIAMTX_VIEWER_AUTH injected at start)
    ├── nginx/docker-entrypoint.sh   # Runs envsubst on template before nginx starts
    ├── mediamtx/docker-entrypoint.sh
    └── app/Dockerfile      # PHP-FPM container
fastapi/                    # YOLO people counter (Python)
```

## Common Commands

### Development (local)
```bash
cd laravel
composer dev          # Starts Laravel server + queue worker + Vite dev server concurrently
```

### Production (Docker)
```bash
# Full stack
docker compose up -d

# Rebuild/restart a single service (e.g., after .env changes)
docker compose up -d laravel

# Run artisan inside container
docker compose exec laravel php artisan migrate
docker compose exec laravel php artisan queue:restart

# Frontend build on production server
source ~/.nvm/nvm.sh && nvm use 22 && npm run build
```

### Testing & Code Quality
```bash
cd laravel
php artisan test                    # Run Pest tests
./vendor/bin/pint                   # PHP code style (Laravel Pint)
npm run lint                        # ESLint
npm run format:check                # Prettier check
```

## Important Patterns

### Security
- **Camera passwords** are encrypted at rest using `Crypt::encryptString()` with a `DecryptException` fallback for legacy plaintext values (see `Camera::password()` Attribute).
- **Settings** with sensitive keys (hemis.token, OAuth secrets) are also encrypted via `Setting` model.
- Model `$hidden` prevents credentials from leaking to the frontend (`Camera::$hidden = ['password', 'username', 'stream_path']`).
- The `AddLinkHeadersForPreloadedAssets` middleware was intentionally removed — it generates large Link headers that overflow OpenResty's upstream buffer, causing 502 errors.

### Permissions (Frontend)
```ts
const { hasPermission } = usePermissions();
const canManage = hasPermission('manage-cameras');
```
Key permissions: `view-cameras`, `manage-cameras`, `view-camera-grid`, `view-auditoriums`, `manage-users`, `view-feedbacks`, `add-feedbacks`, `sync-auditoriums`, `view-lesson-schedules`, `sync-lesson-schedules`.

### MediaMTX Integration
- `MediaMtxService` manages camera paths via the MediaMTX REST API (port 9997).
- Stream URLs: HLS at `/hls/cam_{id}/index.m3u8`, WebRTC at `/webrtc/cam_{id}/whep`.
- FFmpeg uses `-c:a libopus` (not AAC) because WebRTC only supports Opus audio.
- MediaMTX credentials are configured in `mediamtx.yml` and `laravel/.env` (`MEDIAMTX_PASSWORD`).
- Docker `env_file` values are cached at container creation — changing `.env` requires `docker compose up -d` to recreate the container.

### Streaming Snapshots
- Camera snapshots: `storage/app/public/snapshots/camera_{id}_*.jpg` — captured by `CaptureSnapshot` job.
- Feedback snapshots: `storage/app/public/feedback_snapshots/feedback_{id}_cam{id}.jpg` — captured by `CaptureFeedbackSnapshot` job, which tries the camera's ISAPI endpoint first (`/ISAPI/Streaming/channels/101/picture`) and falls back to copying the latest camera snapshot.
- Grid and snapshot endpoints use a single `glob()` scan of the directory to avoid per-camera lookups (prevents 502 timeouts).

### Config Templates
Both `mediamtx.yml.template` and `laravel/docker/nginx/default.conf.template` use `${ENV_VAR}` syntax. The respective docker entrypoints run `envsubst` at container start to produce the actual config files. When editing these files, use `${VAR}` not `$VAR` — unquoted dollar signs without braces will be treated as shell variables by envsubst and cleared.

### Session Management
`Settings/SessionController` reads directly from the `sessions` DB table (not a model). It renders `settings/Sessions.vue` with per-session device info (browser/platform/type parsed from user-agent) and lets users revoke individual or all-other sessions. Revoking all-other sessions requires password confirmation.

### UI Language
The UI is in Uzbek (O'zbek). Flash messages and labels use Uzbek text.

## Deployment
- **Server**: SSH to `admin@100.94.106.116`, project at `/home/admin/Streamer`
- **Proxy chain**: Browser → University's OpenResty proxy → Our Nginx container → PHP-FPM
- The university's OpenResty proxy has a small upstream header buffer. Keep response headers under ~2KB (no large Link headers) or it triggers 502 errors. We cannot change OpenResty's config.
- Redis and PostgreSQL ports are not exposed to host — internal Docker network only
