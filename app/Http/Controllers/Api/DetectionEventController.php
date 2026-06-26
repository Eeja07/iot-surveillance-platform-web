<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DetectionEvent;
use App\Models\ImageRecord;
use App\Events\PersonDetected;
use App\Jobs\SendFcmNotificationJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DetectionEventController extends Controller
{
    /**
     * Store object detection events in the database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image_record_id'         => 'required|exists:image_records,id',
            'detections'               => 'required|array',
            'detections.*.confidence'  => 'required|numeric|min:0|max:1',
            'detections.*.box'         => 'required|array|size:4',
            'detections.*.box.*'       => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors()
            ], 422);
        }

        $createdEvents = [];
        $hasPerson = false;
        foreach ($request->detections as $det) {
            $event = DetectionEvent::create([
                'image_record_id' => $request->image_record_id,
                'object_class'    => 'person',
                'confidence'      => $det['confidence'],
                'x_min'           => $det['box'][0],
                'y_min'           => $det['box'][1],
                'x_max'           => $det['box'][2],
                'y_max'           => $det['box'][3],
            ]);
            
            $createdEvents[] = $event;
            $hasPerson = true;

            // Broadcast real-time event via Reverb
            broadcast(new PersonDetected($event));
        }

        if ($hasPerson) {
            $imageRecord = ImageRecord::with(['camera.user', 'camera.group.user'])->find($request->image_record_id);
            if ($imageRecord && $imageRecord->camera) {
                $camera = $imageRecord->camera;
                $user = $camera->user ?? ($camera->group->user ?? null);
                
                $cooldownKey = "fcm_cooldown_camera_" . $camera->id;
                if (!Cache::has($cooldownKey)) {
                    $cooldown = config('services.firebase.cooldown', 30);
                    Cache::put($cooldownKey, true, now()->addSeconds($cooldown));

                    if ($user && !empty($user->fcm_token)) {
                        SendFcmNotificationJob::dispatch(
                            $user->fcm_token,
                            "Person Detected",
                            "A person was detected on camera: " . $camera->name,
                            [
                                'camera_id' => (string) $camera->id,
                                'image_record_id' => (string) $imageRecord->id,
                            ]
                        );
                        Log::info("FCM Notification queued for user", ['user_id' => $user->id, 'camera_id' => $camera->id]);
                    } else {
                        Log::info("No FCM token found for user of camera", ['camera_id' => $camera->id]);
                    }
                } else {
                    Log::info("FCM Notification suppressed due to cooldown per camera", ['camera_id' => $camera->id]);
                }
            }
        }

        return response()->json([
            'message'          => 'Detection events persisted successfully',
            'detection_events' => $createdEvents
        ], 201);
    }
}
