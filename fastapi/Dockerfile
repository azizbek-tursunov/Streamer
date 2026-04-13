FROM python:3.11-slim

WORKDIR /app

ENV PIP_NO_CACHE_DIR=1

# Install system dependencies for OpenCV
RUN apt-get update && apt-get install -y --no-install-recommends \
    libgl1 \
    libglib2.0-0 \
    && rm -rf /var/lib/apt/lists/*

# Copy requirements first for better caching
COPY requirements.txt .

# Install CPU-only PyTorch first so ultralytics does not pull CUDA packages.
RUN pip install --index-url https://download.pytorch.org/whl/cpu \
    torch \
    torchvision

# Install application dependencies
RUN pip install -r requirements.txt

# Copy application code
COPY . .

# Run the YOLO worker (vanilla Python, no HTTP server)
CMD ["python", "worker.py"]
