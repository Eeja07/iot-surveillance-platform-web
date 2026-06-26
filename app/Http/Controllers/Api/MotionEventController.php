<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MotionEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MotionEventController extends Controller
{
    /**
     * Store a newly created motion event in the database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'camera_id'         => 'required|exists:cameras,id',
            'image_record_id'   => 'required|exists:image_records,id',
            'motion_score'      => 'required|numeric|min:0',
            'person_confidence' => 'nullable|numeric|min:0|max:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors()
            ], 422);
        }

        $motionEvent = MotionEvent::create([
            'camera_id'         => $request->camera_id,
            'image_record_id'   => $request->image_record_id,
            'motion_score'      => $request->motion_score,
            'person_confidence' => $request->person_confidence,
        ]);

        return response()->json([
            'message'      => 'Motion event persisted successfully',
            'motion_event' => $motionEvent
        ], 201);
    }
}
