"""
YOLO People Counter Worker
Vanilla Python worker that consumes jobs from Redis, runs YOLOv8 person detection,
and pushes results back to Redis for Laravel to consume.
"""
import json
import os
import time
from datetime import datetime, timezone

import redis
from ultralytics import YOLO

# Configuration
REDIS_HOST = os.getenv("REDIS_HOST", "localhost")
REDIS_PORT = int(os.getenv("REDIS_PORT", 6379))
REDIS_PREFIX = os.getenv("REDIS_PREFIX", "univision-database-")
CONFIDENCE_THRESHOLD = float(os.getenv("YOLO_CONFIDENCE", 0.3))
PERSON_CLASS_ID = 0  # COCO class 0 = person

# Redis key names (with Laravel prefix)
JOBS_KEY = f"{REDIS_PREFIX}yolo:jobs"
RESULTS_KEY = f"{REDIS_PREFIX}yolo:results"

# Initialize
r = redis.Redis(host=REDIS_HOST, port=REDIS_PORT, decode_responses=True)
model = YOLO("yolov8n.pt")  # Nano model (~6MB, fast inference)

print(f"[YOLO Worker] Started. Redis={REDIS_HOST}:{REDIS_PORT}, Prefix={REDIS_PREFIX}")
print(f"[YOLO Worker] Model loaded: yolov8n.pt")
print(f"[YOLO Worker] Waiting for jobs on '{JOBS_KEY}'...")


def count_people(image_path: str) -> int:
    """Run YOLOv8 inference and count only people (class_id=0)."""
    if not os.path.exists(image_path):
        print(f"[YOLO Worker] File not found: {image_path}")
        return -1

    results = model(image_path, verbose=False)

    people = 0
    for box in results[0].boxes:
        cls_id = int(box.cls)
        conf = float(box.conf)
        if cls_id == PERSON_CLASS_ID and conf >= CONFIDENCE_THRESHOLD:
            people += 1

    return people


def process_job(job_data: dict) -> None:
    """Process a single YOLO job and push result to Redis."""
    camera_id = job_data.get("camera_id")
    image_path = job_data.get("image_path")

    if not camera_id or not image_path:
        print(f"[YOLO Worker] Invalid job data: {job_data}")
        return

    start_time = time.time()
    people_count = count_people(image_path)
    elapsed = round(time.time() - start_time, 2)

    if people_count < 0:
        print(f"[YOLO Worker] Skipping camera {camera_id}: file not found")
        return

    result = {
        "camera_id": camera_id,
        "people_count": people_count,
        "snapshot_path": os.path.basename(image_path),
        "counted_at": datetime.now(timezone.utc).isoformat(),
    }

    r.lpush(RESULTS_KEY, json.dumps(result))
    print(f"[YOLO Worker] Camera {camera_id}: {people_count} people ({elapsed}s)")


def main():
    """Main loop: blocking pop from Redis, process, repeat."""
    while True:
        try:
            # BRPOP blocks until a job is available (timeout=10s then retry)
            result = r.brpop(JOBS_KEY, timeout=10)

            if result is None:
                continue

            _, job_json = result
            job_data = json.loads(job_json)
            process_job(job_data)

        except redis.ConnectionError:
            print("[YOLO Worker] Redis connection lost. Retrying in 5s...")
            time.sleep(5)
        except json.JSONDecodeError as e:
            print(f"[YOLO Worker] Invalid JSON in job: {e}")
        except Exception as e:
            print(f"[YOLO Worker] Unexpected error: {e}")
            time.sleep(1)


if __name__ == "__main__":
    main()
