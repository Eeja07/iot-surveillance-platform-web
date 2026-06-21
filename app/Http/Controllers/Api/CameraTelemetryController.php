<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Camera;
use App\Models\CameraTelemetry;
use Illuminate\Http\Request;

class CameraTelemetryController extends Controller

{
    public function store(Request $request)

    {

        $payload = $request->all();
	\Log::info('RAW_TELEMETRY', $request->all());


        $request->validate([

            'device_id' => 'required|string',

        ]);



        $camera = Camera::where(

            'device_id',

            $payload['device_id']

        )->first();



        if (!$camera) {

            return response()->json([

                'success' => false,

                'message' => 'Camera not found'

            ], 404);

        }



        $payload['camera_id'] = $camera->id;



        $payload['raw_payload'] = json_encode(

            $payload,

            JSON_UNESCAPED_UNICODE

        );



        CameraTelemetry::create($payload);



        return response()->json([

            'success' => true

        ]);

    }

}
