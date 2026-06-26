import threading
from fastapi import FastAPI, BackgroundTasks, HTTPException
from pydantic import BaseModel
from app.worker import QueueWorker
from app.config import Config

app = FastAPI(
    title=Config.APP_NAME,
    description="Python Detection Service Foundation for CCTV / IoT Surveillance",
    version="0.1.0"
)

worker = QueueWorker()

class ProcessImageRequest(BaseModel):
    image_id: int
    image_path: str
    camera_id: int

@app.on_event("startup")
def startup_event():
    # Start the queue worker in a background thread
    worker_thread = threading.Thread(target=worker.start, daemon=True)
    worker_thread.start()

@app.on_event("shutdown")
def shutdown_event():
    worker.stop()

@app.get("/health")
def health_check():
    return {
        "status": "healthy",
        "service": Config.APP_NAME,
        "environment": Config.APP_ENV,
        "queue_provider": Config.QUEUE_PROVIDER,
        "queue_worker_running": worker.running
    }

@app.post("/process-image")
def process_image(payload: ProcessImageRequest, background_tasks: BackgroundTasks):
    # Queue the image processing task to run in the background
    background_tasks.add_task(worker.process_image_task, payload.image_id, payload.image_path, payload.camera_id)
    return {
        "message": "Image processing task queued",
        "image_id": payload.image_id,
        "image_path": payload.image_path,
        "camera_id": payload.camera_id
    }
