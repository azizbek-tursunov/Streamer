"""
YOLO People Counter Worker
Vanilla Python worker that consumes jobs from Redis, runs YOLO person detection,
and pushes results back to Redis for Laravel to consume.
"""
import json
import os
import time
from datetime import datetime, timezone

import redis
from ultralytics import YOLO


def parse_class_ids(raw_value: str) -> list[int]:
    """Parse a comma-separated list of YOLO class ids from the environment."""
    class_ids: list[int] = []
    for item in raw_value.split(","):
        item = item.strip()
        if not item:
            continue
        class_ids.append(int(item))
    return class_ids


# Configuration
REDIS_HOST = os.getenv("REDIS_HOST", "localhost")
REDIS_PORT = int(os.getenv("REDIS_PORT", 6379))
REDIS_PREFIX = os.getenv("REDIS_PREFIX", "univision-database-")
CONFIDENCE_THRESHOLD = float(os.getenv("YOLO_CONFIDENCE", 0.25))
YOLO_MODEL = os.getenv("YOLO_MODEL", "yolo26n.pt")
YOLO_IMAGE_SIZE = int(os.getenv("YOLO_IMAGE_SIZE", 1280))
TARGET_CLASS_IDS = parse_class_ids(os.getenv("YOLO_TARGET_CLASSES", "0"))

# Redis key names (with Laravel prefix)
JOBS_KEY = f"{REDIS_PREFIX}yolo:jobs"
RESULTS_KEY = f"{REDIS_PREFIX}yolo:results"

# Initialize
r = redis.Redis(host=REDIS_HOST, port=REDIS_PORT, decode_responses=True)
model = YOLO(YOLO_MODEL)

print(f"[YOLO Worker] Started. Redis={REDIS_HOST}:{REDIS_PORT}, Prefix={REDIS_PREFIX}")
print(
    "[YOLO Worker] Model loaded: "
    f"{YOLO_MODEL}, confidence={CONFIDENCE_THRESHOLD}, imgsz={YOLO_IMAGE_SIZE}, "
    f"classes={TARGET_CLASS_IDS}"
)
print(f"[YOLO Worker] Waiting for jobs on '{JOBS_KEY}'...")


def count_people(image_path: str) -> int:
    """Run YOLO inference and count detections in the configured target classes."""
    if not os.path.exists(image_path):
        print(f"[YOLO Worker] File not found: {image_path}")
        return -1

    results = model(
        image_path,
        verbose=False,
        conf=CONFIDENCE_THRESHOLD,
        imgsz=YOLO_IMAGE_SIZE,
        classes=TARGET_CLASS_IDS,
    )

    if not results or results[0].boxes is None:
        return 0

    return len(results[0].boxes)


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
