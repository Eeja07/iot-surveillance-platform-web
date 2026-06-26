import os
from dotenv import load_dotenv

# Load environment variables from .env if present
load_dotenv()

class Config:
    APP_NAME: str = os.getenv("APP_NAME", "DetectionService")
    APP_ENV: str = os.getenv("APP_ENV", "local")

    # Queue configuration
    QUEUE_PROVIDER: str = os.getenv("QUEUE_PROVIDER", "redis")  # e.g., redis, rabbitmq, stub
    QUEUE_HOST: str = os.getenv("QUEUE_HOST", "redis")
    QUEUE_PORT: int = int(os.getenv("QUEUE_PORT", 6379))
    QUEUE_NAME: str = os.getenv("QUEUE_NAME", "detection_queue")

    # MQTT configuration
    MQTT_BROKER_HOST: str = os.getenv("MQTT_BROKER_HOST", "emqx")
    MQTT_BROKER_PORT: int = int(os.getenv("MQTT_BROKER_PORT", 1883))

    # MinIO / S3 Storage configuration
    AWS_ENDPOINT: str = os.getenv("AWS_ENDPOINT", "http://cctv-minio:9000")
    AWS_ACCESS_KEY_ID: str = os.getenv("AWS_ACCESS_KEY_ID", "minioadmin")
    AWS_SECRET_ACCESS_KEY: str = os.getenv("AWS_SECRET_ACCESS_KEY", "minioadmin123")
    AWS_DEFAULT_REGION: str = os.getenv("AWS_DEFAULT_REGION", "us-east-1")
    AWS_BUCKET: str = os.getenv("AWS_BUCKET", "cctv")

    # Motion Detection configuration
    MOTION_THRESHOLD: float = float(os.getenv("MOTION_THRESHOLD", 0.5))

    # Laravel API URL configuration
    LARAVEL_API_URL: str = os.getenv("LARAVEL_API_URL", "http://web")
