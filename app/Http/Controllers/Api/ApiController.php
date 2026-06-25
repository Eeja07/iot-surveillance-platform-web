<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiController extends Controller
{
    /**
     * Mengambil status terbaru dari semua kamera milik pengguna yang sedang login.
     * Status ini didasarkan pada accessor 'is_active' di model Camera.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCameraStatuses()
    {
        $cameras = Auth::user()->cameras()->with(['latestTelemetry'])->get();

        $statuses = $cameras->mapWithKeys(function ($camera) {
            $telemetry = $camera->latestTelemetry;
            return [
                $camera->id => [
                    'is_active' => $camera->is_active,
                    'mqtt_status' => $camera->mqtt_status ?? 'offline',
                    'health_status' => $camera->operational_status,
                    'freshness' => $camera->freshness_indicator,
                    'rssi' => $telemetry ? $telemetry->formatted_rssi : 'N/A',
                    'heap' => $telemetry ? $telemetry->formatted_heap : 'N/A',
                    'publish_ms' => $telemetry ? $telemetry->formatted_publish : 'N/A',
                    'mqtt_connected' => $telemetry ? $telemetry->mqtt_status_text : 'N/A',
                    'ws_connected' => $telemetry ? $telemetry->ws_status_text : 'N/A',
                    'mqtt_reconnect' => $telemetry ? $telemetry->reconnect_delta_text : '+0',
                    'ws_close_count' => $telemetry ? $telemetry->ws_close_delta_text : '+0',
                    'publish_fail' => $telemetry ? $telemetry->publish_fail_delta_text : '+0',
                    'uptime' => $telemetry ? $telemetry->formatted_uptime : 'N/A',
                ]
            ];
        });

        return response()->json($statuses);
    }
}
