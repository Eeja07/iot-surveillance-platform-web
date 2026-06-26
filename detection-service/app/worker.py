import time
import logging
import os
import boto3
import requests
from botocore.client import Config as BotoConfig
from app.config import Config
from app.handlers.detection import detect_motion, detect_person

logging.basicConfig(level=logging.INFO, format="%(asctime)s - %(name)s - %(levelname)s - %(message)s")
logger = logging.getLogger("QueueWorker")

class QueueWorker:
    def __init__(self):
        self.running = False
        self.provider = Config.QUEUE_PROVIDER.lower()
        self.queue_name = Config.QUEUE_NAME
        
        # Initialize boto3 S3 client for MinIO
        self.s3_client = boto3.client(
            's3',
            endpoint_url=Config.AWS_ENDPOINT,
            aws_access_key_id=Config.AWS_ACCESS_KEY_ID,
            aws_secret_access_key=Config.AWS_SECRET_ACCESS_KEY,
            config=BotoConfig(signature_version='s3v4'),
            region_name=Config.AWS_DEFAULT_REGION
        )

    def start(self):
        logger.info(f"Starting queue worker using provider: '{self.provider}' for queue: '{self.queue_name}'")
        self.running = True

        if self.provider == "redis":
            self._run_redis_consumer()
        elif self.provider == "rabbitmq":
            self._run_rabbitmq_consumer()
        else:
            self._run_stub_consumer()

    def stop(self):
        logger.info("Stopping queue worker...")
        self.running = False

    def process_image_task(self, image_id: int, image_path: str, camera_id: int):
        logger.info(f"Task received - Image ID: {image_id}, Path: {image_path}, Camera ID: {camera_id}")
        try:
            # Download the image from MinIO / S3
            bucket = Config.AWS_BUCKET
            logger.info(f"Downloading Key: '{image_path}' from Bucket: '{bucket}'")
            
            response = self.s3_client.get_object(Bucket=bucket, Key=image_path)
            image_bytes = response['Body'].read()
            
            # Execute motion detection
            has_motion, motion_score = detect_motion(image_bytes, camera_id, Config.MOTION_THRESHOLD)
            
            if not has_motion:
                logger.info(f"SUCCESS: No motion detected for image ID {image_id} (score: {motion_score:.4f}%). Skipping further processing.")
                return True
                
            logger.info(f"SUCCESS: Motion detected for image ID {image_id} (score: {motion_score:.4f}%). Running YOLO detection...")
            
            # Execute YOLO detection (person class only)
            detections = detect_person(image_bytes)
            person_confidence = max([d["confidence"] for d in detections]) if detections else 0.0
            
            # Persist motion event with person confidence
            self._persist_motion_event(image_id, camera_id, motion_score, person_confidence)
            
            # Persist individual detection events (bounding boxes)
            if detections:
                self._persist_detection_events(image_id, detections)
                
            return True
        except Exception as e:
            logger.error(f"Failed to process image ID {image_id}: {str(e)}")
            return False

    def _persist_motion_event(self, image_id: int, camera_id: int, motion_score: float, person_confidence: float):
        url = f"{Config.LARAVEL_API_URL}/api/motion-events"
        payload = {
            "camera_id": camera_id,
            "image_record_id": image_id,
            "motion_score": float(motion_score),
            "person_confidence": float(person_confidence) if person_confidence > 0.0 else None
        }
        
        max_retries = 3
        backoff_factor = 2
        for attempt in range(max_retries):
            try:
                logger.info(f"Sending motion event to Laravel (Attempt {attempt+1}/{max_retries}): {url}")
                response = requests.post(url, json=payload, timeout=5)
                if response.status_code == 201:
                    logger.info(f"Successfully persisted motion event for image ID {image_id}")
                    return True
                else:
                    logger.warning(f"Laravel returned status {response.status_code}: {response.text}")
            except Exception as e:
                logger.warning(f"Error connecting to Laravel on attempt {attempt+1}: {str(e)}")
            
            if attempt < max_retries - 1:
                sleep_time = backoff_factor ** (attempt + 1)
                logger.info(f"Retrying in {sleep_time} seconds...")
                time.sleep(sleep_time)
                
        logger.error(f"Failed to persist motion event for image ID {image_id} after {max_retries} attempts.")
        return False

    def _persist_detection_events(self, image_id: int, detections: list):
        url = f"{Config.LARAVEL_API_URL}/api/detection-events"
        payload = {
            "image_record_id": image_id,
            "detections": detections
        }
        
        max_retries = 3
        backoff_factor = 2
        for attempt in range(max_retries):
            try:
                logger.info(f"Sending {len(detections)} detection events to Laravel (Attempt {attempt+1}/{max_retries}): {url}")
                response = requests.post(url, json=payload, timeout=5)
                if response.status_code == 201:
                    logger.info(f"Successfully persisted {len(detections)} detection events for image ID {image_id}")
                    return True
                else:
                    logger.warning(f"Laravel returned status {response.status_code}: {response.text}")
            except Exception as e:
                logger.warning(f"Error connecting to Laravel on attempt {attempt+1}: {str(e)}")
                
            if attempt < max_retries - 1:
                sleep_time = backoff_factor ** (attempt + 1)
                logger.info(f"Retrying in {sleep_time} seconds...")
                time.sleep(sleep_time)
                
        logger.error(f"Failed to persist detection events for image ID {image_id} after {max_retries} attempts.")
        return False

    def _run_redis_consumer(self):
        logger.info(f"Connecting to Redis at {Config.QUEUE_HOST}:{Config.QUEUE_PORT}...")
        while self.running:
            logger.debug("Polling Redis queue...")
            time.sleep(5)

    def _run_rabbitmq_consumer(self):
        logger.info(f"Connecting to RabbitMQ at {Config.QUEUE_HOST}:{Config.QUEUE_PORT}...")
        while self.running:
            logger.debug("Polling RabbitMQ queue...")
            time.sleep(5)

    def _run_stub_consumer(self):
        logger.info("Running stub consumer...")
        while self.running:
            logger.debug("Polling stub queue...")
            time.sleep(5)
