# Camera Streamer Platform

A robust, self-hosted streaming platform for managing RTSP cameras, monitoring live feeds, and restreaming to YouTube Live. Built with **Laravel**, **Vue.js**, and **MediaMTX**.

## 🌟 Features

*   **Live Dashboard**: 3x2 Live Mosaic view of active cameras.
*   **Camera Management**: Add, Edit, and Delete RTSP cameras via a clean UI.
*   **YouTube Restreaming**: Push any RTSP feed directly to YouTube Live.
*   **Public Page**: Shareable link (`/streams`) for public viewing.
*   **Status Monitoring**: Automatic tracking of online/offline status.
*   **Localization**: Fully translated into **Uzbek (O'zbek)**.
*   **Efficiency**: Uses Direct Copy (`-c copy`) mode for near-zero CPU usage on the server.

---

## 🚀 Prerequisites

*   **Docker Desktop** (for the Streaming Server)
*   **PHP 8.2+** (with SQLite/MySQL extensions)
*   **Node.js 20+** (for frontend build)
*   **Composer** (PHP dependency manager)

---

## 🛠️ Installation

### 1. Clone the Repository
```bash
git clone https://github.com/azizbek-tursunov/Streamer.git
cd Streamer
```

### 2. Install Dependencies
```bash
# Backend
composer install

# Frontend
npm install
```

### 3. Environment Setup
Copy the example environment file and configure it:
```bash
cp .env.example .env
php artisan key:generate
```
*Note: The default database is SQLite. It works out of the box.*

### 4. Database Migration
```bash
touch database/database.sqlite
php artisan migrate --seed
```

---

## 🏃 Running the Platform

This platform consists of two parts: the **Web App** (Laravel) and the **Streaming Server** (MediaMTX via Docker).

### Step 1: Start the Streaming Server (Critical)
The application relies on MediaMTX to handle RTSP streams.
```bash
docker-compose up -d
```
*This starts a MediaMTX container on ports `8554` (RTSP), `8888` (HLS), and `9997` (API).*

### Step 2: Start the Web Application
```bash
# Backend Server
php artisan serve

# Frontend Builder (in a new terminal)
npm run dev
```

**Access the Dashboard:** [http://localhost:8000/dashboard](http://localhost:8000/dashboard)

---

## 📹 Camera Configuration Guide

For the best performance and compatibility, configure your physical cameras as follows:

1.  **Video Codec**: **H.264** (Must be H.264; H.265/HEVC is compatible but not recommended for web playback without transcoding hardware).
2.  **Audio Codec**: AAC (Recommended).
3.  **FPS**: 15-30 FPS.
4.  **Bitrate**: 2000-4000 Kbps (depending on network).

### Adding a Camera
1.  Go to **Kameralar** (Cameras).
2.  Click **Kamera Qo'shish** (Add Camera).
3.  Enter:
    *   **Name**: E.g., "Front Gate"
    *   **IP Address**: E.g., `192.168.1.50`
    *   **Port**: Default is `554`.
    *   **Username/Password**: (If required by camera).
4.  Click **Saqlash** (Save).

---

## 🔴 YouTube Restreaming

To restream a camera to YouTube:
1.  Open the Camera details page.
2.  Click **Sozlash** (Configure) next to YouTube URL.
3.  Paste your **YouTube Stream Key** (from YouTube Studio).
    *   *The system automatically formatting the full RTMP URL.*
4.  Toggle **YouTube Restream** ON.
5.  Click **Start Stream**.

---

## 🧪 Running Tests

To verify the system is working correctly:
```bash
php artisan test
```

---

## 📚 Tech Stack
*   **Backend**: Laravel 12
*   **Frontend**: Vue 3 + Inertia.js + TailwindCSS + Shadcn UI
*   **Streaming**: MediaMTX (Docker) + FFmpeg
*   **Database**: SQLite

---

## 📄 License
MIT License.
