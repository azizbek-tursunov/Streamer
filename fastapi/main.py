import os
from pathlib import Path

from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from ultralytics import YOLO

app = FastAPI(title="UniVision Realtime YOLO")

CONFIDENCE_THRESHOLD = float(os.getenv("YOLO_CONFIDENCE", 0.3))
YOLO_MODEL_PATH = os.getenv("YOLO_MODEL", "yolov8x.pt")
PERSON_CLASS_ID = 0
SNAPSHOT_ROOT = Path(os.getenv("SNAPSHOT_ROOT", "/var/www/html/storage/app/public/snapshots")).resolve()

model: YOLO | None = None


class CountRequest(BaseModel):
    camera_id: int
    image_path: str


def get_model() -> YOLO:
    global model

    if model is None:
        model = YOLO(YOLO_MODEL_PATH)

    return model


def resolve_image_path(image_path: str) -> Path:
    path = Path(image_path).resolve()

    if SNAPSHOT_ROOT not in path.parents:
        raise HTTPException(status_code=422, detail="Image path outside snapshots directory")

    if not path.exists():
        raise HTTPException(status_code=404, detail="Snapshot not found")

    return path


@app.get("/")
async def root():
    return {
        "status": "ok",
        "service": "univision-realtime-yolo",
        "model": YOLO_MODEL_PATH,
        "confidence": CONFIDENCE_THRESHOLD,
    }


@app.get("/health")
async def health():
    return {"status": "healthy"}


@app.post("/count")
async def count_people(request: CountRequest):
    image_path = resolve_image_path(request.image_path)
    results = get_model()(str(image_path), verbose=False)

    people = 0
    for box in results[0].boxes:
        cls_id = int(box.cls)
        conf = float(box.conf)
        if cls_id == PERSON_CLASS_ID and conf >= CONFIDENCE_THRESHOLD:
            people += 1

    return {
        "camera_id": request.camera_id,
        "people_count": people,
        "image_path": str(image_path),
        "model": YOLO_MODEL_PATH,
        "confidence": CONFIDENCE_THRESHOLD,
    }
