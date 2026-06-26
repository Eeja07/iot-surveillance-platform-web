import cv2
import numpy as np
import logging
import time
from ultralytics import YOLO

logger = logging.getLogger("DetectionHandler")

# Dictionary to hold BackgroundSubtractorMOG2 instances per camera as tuples: (subtractor, last_active_timestamp)
_subtractors = {}
SUBC_CLEANUP_THRESHOLD = 3600 # Clean up subtractors inactive for 1 hour

# Load YOLO11n model once at module initialization
try:
    logger.info("Loading YOLO11n model...")
    _yolo_model = YOLO("yolo11n.pt")
    logger.info("YOLO11n model loaded successfully.")
except Exception as e:
    logger.error(f"Error loading YOLO11n model: {str(e)}")
    _yolo_model = None

def get_subtractor(camera_id: int):
    now = time.time()
    # Clean up stale camera subtractors to avoid memory leaks
    stale_keys = [k for k, v in _subtractors.items() if now - v[1] > SUBC_CLEANUP_THRESHOLD]
    for k in stale_keys:
        logger.info(f"Cleaning up inactive BackgroundSubtractorMOG2 for camera ID: {k}")
        del _subtractors[k]

    if camera_id not in _subtractors:
        logger.info(f"Initializing BackgroundSubtractorMOG2 for camera ID: {camera_id}")
        # history=500, varThreshold=16, detectShadows=False
        sub = cv2.createBackgroundSubtractorMOG2(
            history=500, 
            varThreshold=16, 
            detectShadows=False
        )
        _subtractors[camera_id] = (sub, now)
    else:
        sub, _ = _subtractors[camera_id]
        _subtractors[camera_id] = (sub, now)
        
    return sub

def detect_motion(image_bytes: bytes, camera_id: int, threshold: float = 0.5):
    """
    Detects motion in an image using BackgroundSubtractorMOG2.
    Returns a tuple of (has_motion, motion_score).
    """
    # Decode the image bytes to OpenCV format (numpy array)
    nparr = np.frombuffer(image_bytes, np.uint8)
    frame = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
    
    if frame is None:
        logger.error(f"Failed to decode image bytes for camera ID: {camera_id}")
        return False, 0.0
        
    # Get the background subtractor for this camera
    subtractor = get_subtractor(camera_id)
    
    # Apply the subtractor to the frame to get foreground mask
    fg_mask = subtractor.apply(frame)
    
    # Calculate motion score as percentage of non-zero (white/foreground) pixels
    non_zero_count = np.count_nonzero(fg_mask)
    total_pixels = frame.shape[0] * frame.shape[1]
    
    if total_pixels == 0:
        return False, 0.0
        
    motion_score = (non_zero_count / total_pixels) * 100.0
    
    has_motion = motion_score >= threshold
    
    logger.info(f"Motion analysis for camera ID {camera_id}: score = {motion_score:.4f}%, threshold = {threshold}%, has_motion = {has_motion}")
    
    return has_motion, motion_score

def detect_person(image_bytes: bytes) -> list:
    """
    Runs YOLO11n on the image.
    Looks for the 'person' class (class ID 0 in COCO dataset).
    Returns a list of dictionaries with bounding box and confidence.
    """
    if _yolo_model is None:
        logger.warning("YOLO model not loaded, skipping person detection.")
        return []

    nparr = np.frombuffer(image_bytes, np.uint8)
    frame = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
    
    if frame is None:
        logger.error("Failed to decode image bytes for YOLO inference")
        return []
        
    # Run inference without console printing
    results = _yolo_model(frame, verbose=False)
    
    detections = []
    for r in results:
        for box in r.boxes:
            class_id = int(box.cls[0])
            # class 0 is 'person' in COCO dataset
            if class_id == 0:
                conf = float(box.conf[0])
                # xyxy is [x1, y1, x2, y2]
                xyxy = box.xyxy[0].tolist()
                detections.append({
                    "confidence": conf,
                    "box": xyxy
                })
                logger.info(f"Detected class person with confidence: {conf:.4f}, box: {xyxy}")
                    
    return detections
