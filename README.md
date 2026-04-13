# UniVision - Camera Streaming Platform

A self-hosted streaming platform for managing RTSP cameras, monitoring live feeds via HLS/WebRTC, people counting with YOLO, and restreaming to YouTube Live. Built with Laravel 12, Vue 3, and MediaMTX.

## Features

- **Live Dashboard** - real-time mosaic view of active cameras
- **Camera Management** - add, edit, delete RTSP cameras with encrypted credentials
- **HLS & WebRTC Streaming** - low-latency video with Opus audio support
- **YouTube Restreaming** - push any camera feed to YouTube Live
- **People Counting** - YOLO26-based person detection with Redis queue
- **HEMIS Integration** - OAuth login, user/faculty/auditorium sync from university system
- **Role-Based Access** - Spatie Permission with granular permissions
- **Public Stream Page** - shareable `/streams` page for public cameras
- **Lesson Feedback** - auditorium-linked feedback system
- **Localization** - fully translated to Uzbek

## Architecture

```
Browser --> Reverse Proxy (optional) --> Nginx (port 80/443) --> PHP-FPM (Laravel)
                                              |
                                              +--> MediaMTX (HLS/WebRTC/RTSP)

Laravel <--Redis Queue--> YOLO Worker (FastAPI/Python)
Laravel <--PostgreSQL--> Database
```

### Docker Services

| Service    | Image                              | Purpose                           |
|------------|-------------------------------------|-----------------------------------|
| laravel    | Custom PHP 8.4-FPM                  | Web app + queue workers + scheduler |
| nginx      | nginx:alpine                        | Reverse proxy, serves static files |
| mediamtx   | bluenviron/mediamtx:latest-ffmpeg   | RTSP/HLS/WebRTC streaming server  |
| redis      | redis:alpine                        | Queue broker (Laravel <-> YOLO)   |
| pgsql      | postgres:17-alpine                  | PostgreSQL database               |
| yolo       | Custom Python                       | People counting worker            |
| certbot    | certbot/certbot                     | SSL certificate renewal           |

## Prerequisites

- **Linux server** (Ubuntu 22.04+ recommended)
- **Docker** and **Docker Compose** v2+
- **Node.js 20+** with npm (for frontend build)
- **Git**
- A domain name pointed to the server's public IP (for SSL)

## Installation

### 1. Clone the repository

```bash
git clone https://github.com/azizbek-tursunov/Streamer.git
cd Streamer
```

### 2. Generate credentials

Generate strong passwords for all services:

```bash
# Generate random passwords
ADMIN_PASS=$(openssl rand -base64 16 | tr -d '/+=' | head -c 20)
VIEWER_PASS=$(openssl rand -base64 16 | tr -d '/+=' | head -c 16)
DB_PASS=$(openssl rand -base64 16 | tr -d '/+=' | head -c 20)
VIEWER_AUTH=$(echo -n "viewer:${VIEWER_PASS}" | base64)

echo "DB_PASSWORD: $DB_PASS"
echo "MEDIAMTX_PASSWORD: $ADMIN_PASS"
echo "MEDIAMTX_VIEWER_PASSWORD: $VIEWER_PASS"
echo "MEDIAMTX_VIEWER_AUTH: $VIEWER_AUTH"
```

### 3. Create root `.env` (for Docker Compose)

```bash
cat > .env << EOF
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=${DB_PASS}

MEDIAMTX_USER=admin
MEDIAMTX_PASSWORD=${ADMIN_PASS}
MEDIAMTX_VIEWER_USER=viewer
MEDIAMTX_VIEWER_PASSWORD=${VIEWER_PASS}
MEDIAMTX_VIEWER_AUTH=${VIEWER_AUTH}
EOF
```

### 4. Create Laravel `.env`

```bash
cp laravel/.env.example laravel/.env
```

Edit `laravel/.env` with the following values:

```env
APP_NAME=UniVision
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=<same DB_PASS from step 2>

REDIS_HOST=redis

MEDIAMTX_HOST=mediamtx
MEDIAMTX_API_PORT=9997
MEDIAMTX_USER=admin
MEDIAMTX_PASSWORD=<same ADMIN_PASS from step 2>

SESSION_ENCRYPT=true
SESSION_LIFETIME=60
```

### 5. Configure nginx and MediaMTX templates

**Nginx** - edit `laravel/docker/nginx/default.conf.template`:
- Change `server_name vision.namdu.uz` to your domain

**MediaMTX** - edit `laravel/mediamtx.yml.template`:
- Change `webrtcAdditionalHosts: [84.54.115.89]` to your server's public IP

### 6. Install dependencies and build frontend

```bash
cd laravel

# Install PHP dependencies
docker run --rm -v $(pwd):/app -w /app composer:latest composer install --no-dev --optimize-autoloader

# Install and build frontend
npm install
npm run build

cd ..
```

### 7. Start all services

```bash
docker compose up -d
```

This will:
- Build the Laravel and YOLO Docker images
- Start PostgreSQL, Redis, MediaMTX, Nginx
- Run database migrations automatically on first start
- Initialize camera streams

### 8. Generate application key

```bash
docker compose exec laravel php artisan key:generate
```

### 9. Create storage symlink

```bash
docker compose exec laravel php artisan storage:link
```

### 10. Create the first admin user

```bash
docker compose exec laravel php artisan tinker
```

```php
$user = \App\Models\User::create([
    'name' => 'Admin',
    'email' => 'admin@example.com',
    'password' => bcrypt('your-password'),
]);
$user->assignRole('super-admin');
exit;
```

### 11. Set up SSL (optional but recommended)

```bash
# Initial certificate
docker compose run --rm certbot certonly --webroot \
    --webroot-path /var/www/certbot \
    -d your-domain.com

# Restart nginx to load the certificate
docker compose restart nginx
```

You'll also need to add SSL configuration to the nginx template. See the nginx docs for HTTPS setup.

## Post-Installation

### Set file permissions

```bash
sudo chown -R 1000:1000 laravel/storage laravel/bootstrap/cache
chmod -R 775 laravel/storage laravel/bootstrap/cache
```

### Verify all services are healthy

```bash
docker compose ps
```

All services should show `healthy` or `running`.

### Open required ports

| Port | Protocol | Purpose |
|------|----------|---------|
| 80   | TCP      | HTTP    |
| 443  | TCP      | HTTPS   |
| 8554 | TCP      | RTSP (camera input) |
| 8189 | UDP+TCP  | WebRTC media |

Ports 1935 (RTMP), 8888 (HLS), 8889 (WebRTC signaling), and 9997 (MediaMTX API) are used internally between containers and do not need to be exposed externally unless accessed directly.

## Camera Configuration

For best compatibility, configure your IP cameras with:

- **Video Codec**: H.264 (required for web playback)
- **Audio Codec**: AAC (transcoded to Opus for WebRTC automatically)
- **Resolution**: 1920x1080 or lower
- **FPS**: 15-25
- **Bitrate**: 2000-4000 Kbps

### Adding a camera

1. Log in as admin
2. Go to **Kameralar** (Cameras)
3. Click **Kamera Qo'shish** (Add Camera)
4. Enter: Name, IP Address, Port (default 554), Username/Password, Stream Path
5. Click **Saqlash** (Save)

The camera will automatically be registered with MediaMTX and start streaming.

## Common Operations

### View logs

```bash
# All services
docker compose logs -f

# Specific service
docker compose logs -f laravel
docker compose logs -f mediamtx
docker compose logs -f nginx
```

### Restart a service

```bash
docker compose restart laravel
```

### Rebuild after code changes

```bash
git pull
cd laravel && npm run build && cd ..
docker compose up -d laravel    # recreates container with fresh env
```

### Run artisan commands

```bash
docker compose exec laravel php artisan <command>
```

### Database backup

```bash
docker compose exec pgsql pg_dump -U laravel laravel > backup_$(date +%Y%m%d).sql
```

### Database restore

```bash
cat backup.sql | docker compose exec -T pgsql psql -U laravel laravel
```

## Troubleshooting

### 502 Bad Gateway
- If behind a reverse proxy (e.g., OpenResty), ensure it has sufficient upstream buffer size. This platform keeps response headers small (~1KB) to avoid buffer overflow.
- Check nginx logs: `docker compose logs nginx`

### Streams not loading
- Verify MediaMTX is healthy: `docker compose ps mediamtx`
- Check MediaMTX logs: `docker compose logs mediamtx`
- Verify camera is reachable from the server: `docker compose exec mediamtx ffprobe rtsp://user:pass@camera-ip:554/stream`

### WebRTC not working
- Ensure `webrtcAdditionalHosts` in `mediamtx.yml.template` contains your server's public IP
- Ensure port 8189 (UDP+TCP) is open in your firewall
- WebRTC only supports Opus audio; AAC is automatically transcoded via FFmpeg

### Container won't start after .env change
Docker caches `env_file` values at container creation. After changing `.env`, recreate the container:
```bash
docker compose up -d laravel
```

## Tech Stack

- **Backend**: Laravel 12 + PHP 8.4 + Inertia.js v2
- **Frontend**: Vue 3 + TypeScript + Tailwind CSS 4 + shadcn-vue
- **Streaming**: MediaMTX + FFmpeg (RTSP relay with Opus audio)
- **Database**: PostgreSQL 17
- **Queue**: Redis + Laravel Horizon
- **Auth**: Laravel Fortify + Spatie Permission + HEMIS OAuth
- **AI**: YOLO26 people counting (Python/FastAPI)

## Classroom Counting

The production worker now defaults to `yolo26n.pt` with person-only detection tuned for CPU deployment. This is suitable as a bootstrap model, but classroom occupancy accuracy should be improved with a custom fine-tuned dataset collected from your own camera views.

Training scaffold files are provided here:

- `fastapi/training/README.md`
- `fastapi/training/classroom_people.template.yaml`

## License

MIT License.
