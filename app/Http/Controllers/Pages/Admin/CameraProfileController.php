<?php

namespace App\Http\Controllers\Pages\Admin;

use App\Http\Controllers\Controller;
use App\Models\CameraProfile;
use Illuminate\Http\Request;

class CameraProfileController extends Controller
{
    public function store(Request $request)
    {
        if ($request->has('duplicate_from_id')) {
            $original = CameraProfile::findOrFail($request->duplicate_from_id);
            $newProfile = $original->replicate();
            $newProfile->name = $this->getUniqueProfileName($original->name . ' - Copy');
            $newProfile->save();

            return redirect()->back()->with('success', 'Profile duplicated successfully.');
        }

        $validated = $request->validate([
            'name' => 'required|string|unique:camera_profiles,name',
            'jpeg_quality' => 'required|integer|between:10,63',
            'frame_size' => 'required|string|in:QQVGA,QVGA,VGA,SVGA,XGA,SXGA,UXGA',
            'capture_interval_ms' => 'required|integer|min:100',
            'telemetry_interval_ms' => 'required|integer|min:1000',
            'mqtt_buffer' => 'required|integer|min:0',
            'image_enabled' => 'boolean',
            'telemetry_enabled' => 'boolean',
            'ota_enabled' => 'boolean',
            'restart_required' => 'boolean',
        ]);

        $validated['image_enabled'] = $request->has('image_enabled');
        $validated['telemetry_enabled'] = $request->has('telemetry_enabled');
        $validated['ota_enabled'] = $request->has('ota_enabled');
        $validated['restart_required'] = $request->has('restart_required');

        CameraProfile::create($validated);

        return redirect()->back()->with('success', 'Profile created successfully.');
    }

    public function update(Request $request, $id)
    {
        $profile = CameraProfile::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|unique:camera_profiles,name,' . $profile->id,
            'jpeg_quality' => 'required|integer|between:10,63',
            'frame_size' => 'required|string|in:QQVGA,QVGA,VGA,SVGA,XGA,SXGA,UXGA',
            'capture_interval_ms' => 'required|integer|min:100',
            'telemetry_interval_ms' => 'required|integer|min:1000',
            'mqtt_buffer' => 'required|integer|min:0',
            'image_enabled' => 'boolean',
            'telemetry_enabled' => 'boolean',
            'ota_enabled' => 'boolean',
            'restart_required' => 'boolean',
        ]);

        $validated['image_enabled'] = $request->has('image_enabled');
        $validated['telemetry_enabled'] = $request->has('telemetry_enabled');
        $validated['ota_enabled'] = $request->has('ota_enabled');
        $validated['restart_required'] = $request->has('restart_required');

        $profile->update($validated);

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }

    public function destroy($id)
    {
        $profile = CameraProfile::findOrFail($id);

        // Prevent deletion of Default profiles if you want, or just delete
        if (in_array($profile->name, ['Low Bandwidth', 'Balanced', 'High Quality', 'Custom'])) {
            return redirect()->back()->with('error', 'Cannot delete system default profiles.');
        }

        $profile->delete();

        return redirect()->back()->with('success', 'Profile deleted successfully.');
    }

    protected function getUniqueProfileName($baseName)
    {
        $name = $baseName;
        $counter = 1;
        while (CameraProfile::where('name', $name)->exists()) {
            $name = $baseName . ' (' . $counter . ')';
            $counter++;
        }
        return $name;
    }
}
