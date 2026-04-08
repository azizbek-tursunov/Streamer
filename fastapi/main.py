"""
FastAPI YOLO Worker - Queue Consumer
Placeholder service for YOLO processing
"""
import os
from fastapi import FastAPI

app = FastAPI(title="UniVision YOLO Worker")

REDIS_HOST = os.getenv("REDIS_HOST", "localhost")
REDIS_PORT = int(os.getenv("REDIS_PORT", 6379))


@app.get("/")
async def root():
    return {"status": "ok", "service": "univision-yolo-worker"}


@app.get("/health")
async def health():
    return {"status": "healthy"}
