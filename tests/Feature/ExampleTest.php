<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Camera;
use App\Models\ImageRecord;
use App\Models\DetectionEvent;
use App\Models\MotionEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_detection_endpoints_require_authentication(): void
    {
        $this->getJson('/api/detection-events')->assertStatus(401);
        $this->getJson('/api/detection-events/latest')->assertStatus(401);
        $this->getJson('/api/detection-events/camera/1')->assertStatus(401);
        $this->getJson('/api/detection-events/history')->assertStatus(401);
        $this->getJson('/api/motion-events')->assertStatus(401);
    }

    public function test_can_retrieve_detection_events(): void
    {
        $user = User::factory()->create();
        $camera = Camera::create([
            'user_id' => $user->id,
            'name' => 'TW2-603',
        ]);
        $image = ImageRecord::create([
            'camera_id' => $camera->id,
            'path' => 'camera/test/image.jpg',
            'captured_at' => now(),
        ]);
        $event = DetectionEvent::create([
            'image_record_id' => $image->id,
            'object_class' => 'person',
            'confidence' => 0.93,
            'x_min' => 0,
            'y_min' => 0,
            'x_max' => 1,
            'y_max' => 1,
        ]);

        $response = $this->actingAs($user)->getJson('/api/detection-events');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'camera_id',
                        'camera_name',
                        'image_record_id',
                        'image_url',
                        'confidence',
                        'detected_at'
                    ]
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'total'
                ]
            ]);

        $this->assertEquals($event->id, $response->json('data.0.id'));
        $this->assertEquals(0.93, $response->json('data.0.confidence'));
        $this->assertEquals('TW2-603', $response->json('data.0.camera_name'));
    }

    public function test_can_retrieve_latest_detection_event(): void
    {
        $user = User::factory()->create();
        $camera = Camera::create([
            'user_id' => $user->id,
            'name' => 'TW2-603',
        ]);
        $image = ImageRecord::create([
            'camera_id' => $camera->id,
            'path' => 'camera/test/image.jpg',
            'captured_at' => now(),
        ]);
        $event1 = DetectionEvent::create([
            'image_record_id' => $image->id,
            'object_class' => 'person',
            'confidence' => 0.80,
            'x_min' => 0, 'y_min' => 0, 'x_max' => 1, 'y_max' => 1,
        ]);
        $event2 = DetectionEvent::create([
            'image_record_id' => $image->id,
            'object_class' => 'person',
            'confidence' => 0.93,
            'x_min' => 0, 'y_min' => 0, 'x_max' => 1, 'y_max' => 1,
        ]);

        $response = $this->actingAs($user)->getJson('/api/detection-events/latest');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $event2->id,
                'camera_id' => $camera->id,
                'camera_name' => 'TW2-603',
                'image_record_id' => $image->id,
                'confidence' => 0.93,
            ]);
    }

    public function test_camera_specific_detections_returns_forbidden_if_not_owned(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $camera = Camera::create([
            'user_id' => $otherUser->id,
            'name' => 'Other Camera',
        ]);

        $this->actingAs($user)
            ->getJson("/api/detection-events/camera/{$camera->id}")
            ->assertStatus(403);
    }

    public function test_can_retrieve_camera_specific_detections(): void
    {
        $user = User::factory()->create();
        $camera = Camera::create([
            'user_id' => $user->id,
            'name' => 'TW2-603',
        ]);
        $image = ImageRecord::create([
            'camera_id' => $camera->id,
            'path' => 'camera/test/image.jpg',
            'captured_at' => now(),
        ]);
        $event = DetectionEvent::create([
            'image_record_id' => $image->id,
            'object_class' => 'person',
            'confidence' => 0.93,
            'x_min' => 0, 'y_min' => 0, 'x_max' => 1, 'y_max' => 1,
        ]);

        $response = $this->actingAs($user)->getJson("/api/detection-events/camera/{$camera->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.0.id', $event->id);
    }

    public function test_can_retrieve_history_with_date_and_camera_filter(): void
    {
        $user = User::factory()->create();
        $camera1 = Camera::create(['user_id' => $user->id, 'name' => 'TW2-603']);
        $camera2 = Camera::create(['user_id' => $user->id, 'name' => 'TW2-604']);

        $image1 = ImageRecord::create(['camera_id' => $camera1->id, 'path' => 'cam1.jpg', 'captured_at' => now()]);
        $image2 = ImageRecord::create(['camera_id' => $camera2->id, 'path' => 'cam2.jpg', 'captured_at' => now()]);

        $event1 = DetectionEvent::create([
            'image_record_id' => $image1->id,
            'object_class' => 'person',
            'confidence' => 0.93,
            'x_min' => 0, 'y_min' => 0, 'x_max' => 1, 'y_max' => 1,
        ]);
        $event2 = DetectionEvent::create([
            'image_record_id' => $image2->id,
            'object_class' => 'person',
            'confidence' => 0.85,
            'x_min' => 0, 'y_min' => 0, 'x_max' => 1, 'y_max' => 1,
        ]);

        $response = $this->actingAs($user)->getJson("/api/detection-events/history?camera_id={$camera1->id}");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($event1->id, $response->json('data.0.id'));
    }

    public function test_can_retrieve_motion_events(): void
    {
        $user = User::factory()->create();
        $camera = Camera::create([
            'user_id' => $user->id,
            'name' => 'TW2-603',
        ]);
        $image = ImageRecord::create([
            'camera_id' => $camera->id,
            'path' => 'camera/test/image.jpg',
            'captured_at' => now(),
        ]);
        $motion = MotionEvent::create([
            'camera_id' => $camera->id,
            'image_record_id' => $image->id,
            'motion_score' => 0.45,
            'person_confidence' => 0.85,
        ]);

        $response = $this->actingAs($user)->getJson('/api/motion-events');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'camera_id',
                        'camera_name',
                        'image_record_id',
                        'image_url',
                        'motion_score',
                        'person_confidence',
                        'detected_at'
                    ]
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'total'
                ]
            ]);

        $this->assertEquals($motion->id, $response->json('data.0.id'));
        $this->assertEquals(0.45, $response->json('data.0.motion_score'));
        $this->assertEquals(0.85, $response->json('data.0.person_confidence'));
    }

    public function test_notification_endpoints_require_authentication(): void
    {
        $this->getJson('/api/notifications')->assertStatus(401);
        $this->postJson('/api/notifications/1/read')->assertStatus(401);
    }

    public function test_can_retrieve_notifications(): void
    {
        $user = User::factory()->create();
        $camera = Camera::create([
            'user_id' => $user->id,
            'name' => 'TW2-603',
        ]);
        $image = ImageRecord::create([
            'camera_id' => $camera->id,
            'path' => 'camera/test/image.jpg',
            'captured_at' => now(),
        ]);
        $event = DetectionEvent::create([
            'image_record_id' => $image->id,
            'object_class' => 'person',
            'confidence' => 0.93,
            'x_min' => 0, 'y_min' => 0, 'x_max' => 1, 'y_max' => 1,
        ]);

        $response = $this->actingAs($user)->getJson('/api/notifications');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'message',
                        'camera_id',
                        'camera_name',
                        'image_url',
                        'is_read',
                        'created_at'
                    ]
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'total'
                ]
            ]);

        $this->assertEquals($event->id, $response->json('data.0.id'));
        $this->assertEquals('Human Detected', $response->json('data.0.title'));
        $this->assertEquals('Person detected on TW2-603', $response->json('data.0.message'));
        $this->assertFalse($response->json('data.0.is_read'));
    }

    public function test_can_mark_notification_as_read(): void
    {
        $user = User::factory()->create();
        $camera = Camera::create([
            'user_id' => $user->id,
            'name' => 'TW2-603',
        ]);
        $image = ImageRecord::create([
            'camera_id' => $camera->id,
            'path' => 'camera/test/image.jpg',
            'captured_at' => now(),
        ]);
        $event = DetectionEvent::create([
            'image_record_id' => $image->id,
            'object_class' => 'person',
            'confidence' => 0.93,
            'x_min' => 0, 'y_min' => 0, 'x_max' => 1, 'y_max' => 1,
        ]);

        // Verify initially unread
        $response = $this->actingAs($user)->getJson('/api/notifications');
        $this->assertFalse($response->json('data.0.is_read'));

        // Mark as read
        $this->actingAs($user)->postJson("/api/notifications/{$event->id}/read")
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        // Verify marked as read
        $response = $this->actingAs($user)->getJson('/api/notifications');
        $this->assertTrue($response->json('data.0.is_read'));
    }

    public function test_cannot_mark_other_users_notification_as_read(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $camera = Camera::create([
            'user_id' => $otherUser->id,
            'name' => 'Other Camera',
        ]);
        $image = ImageRecord::create([
            'camera_id' => $camera->id,
            'path' => 'camera/test/image.jpg',
            'captured_at' => now(),
        ]);
        $event = DetectionEvent::create([
            'image_record_id' => $image->id,
            'object_class' => 'person',
            'confidence' => 0.93,
            'x_min' => 0, 'y_min' => 0, 'x_max' => 1, 'y_max' => 1,
        ]);

        $this->actingAs($user)->postJson("/api/notifications/{$event->id}/read")
            ->assertStatus(404);
    }

    public function test_overview_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/overview')->assertStatus(401);
    }

    public function test_can_retrieve_overview_statistics(): void
    {
        $user = User::factory()->create();

        // Camera 1: Online (latest_image_at is now, telemetry is good)
        $camera1 = Camera::create([
            'user_id' => $user->id,
            'name' => 'TW2-603',
            'latest_image_at' => now(),
        ]);

        // Camera 2: Offline (latest_image_at is 5 minutes ago)
        $camera2 = Camera::create([
            'user_id' => $user->id,
            'name' => 'TW2-604',
            'latest_image_at' => now()->subMinutes(5),
        ]);

        // Telemetry for Camera 1
        \App\Models\CameraTelemetry::create([
            'camera_id' => $camera1->id,
            'device_id' => $camera1->device_id,
            'rssi' => -60,
            'free_heap' => 180000,
            'uptime_sec' => 100000,
        ]);

        // Telemetry for Camera 2
        \App\Models\CameraTelemetry::create([
            'camera_id' => $camera2->id,
            'device_id' => $camera2->device_id,
            'rssi' => -70,
            'free_heap' => 190000,
            'uptime_sec' => 200000,
        ]);

        // Create some images (10 images total)
        for ($i = 0; $i < 6; $i++) {
            ImageRecord::create([
                'camera_id' => $camera1->id,
                'path' => "camera/test/img_{$i}.jpg",
                'captured_at' => now(),
            ]);
        }
        for ($i = 0; $i < 4; $i++) {
            ImageRecord::create([
                'camera_id' => $camera2->id,
                'path' => "camera/test/img_offline_{$i}.jpg",
                'captured_at' => now(),
            ]);
        }

        // Create some detection events today
        $image1 = ImageRecord::where('camera_id', $camera1->id)->first();
        DetectionEvent::create([
            'image_record_id' => $image1->id,
            'object_class' => 'person',
            'confidence' => 0.93,
            'x_min' => 0, 'y_min' => 0, 'x_max' => 1, 'y_max' => 1,
        ]);

        // Create some motion events today
        MotionEvent::create([
            'camera_id' => $camera1->id,
            'image_record_id' => $image1->id,
            'motion_score' => 0.45,
            'person_confidence' => 0.85,
        ]);

        $response = $this->actingAs($user)->getJson('/api/overview');

        $response->assertStatus(200)
            ->assertJson([
                'online_cameras' => 1,
                'total_cameras' => 2,
                'detections_today' => 1,
                'motions_today' => 1,
                'storage_usage_gb' => 0, // 10 images * 50KB / 1MB = 0GB
                'avg_rssi' => -65,      // (-60 + -70) / 2
                'avg_heap' => 185000,   // (180000 + 190000) / 2
                'uptime_avg' => 150000, // (100000 + 200000) / 2
            ]);
    }

    public function test_config_endpoints_require_authentication(): void
    {
        $this->getJson('/api/cameras/1/config')->assertStatus(401);
        $this->putJson('/api/cameras/1/config', [])->assertStatus(401);
        $this->postJson('/api/cameras/1/reboot')->assertStatus(401);
        $this->postJson('/api/cameras/1/capture')->assertStatus(401);
    }

    public function test_cannot_access_other_users_camera_config(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $camera = Camera::create([
            'user_id' => $otherUser->id,
            'name' => 'Other Camera',
        ]);

        $this->actingAs($user)->getJson("/api/cameras/{$camera->id}/config")->assertStatus(403);
        $this->actingAs($user)->putJson("/api/cameras/{$camera->id}/config", [])->assertStatus(403);
        $this->actingAs($user)->postJson("/api/cameras/{$camera->id}/reboot")->assertStatus(403);
        $this->actingAs($user)->postJson("/api/cameras/{$camera->id}/capture")->assertStatus(403);
    }

    public function test_can_retrieve_camera_config(): void
    {
        $user = User::factory()->create();
        $camera = Camera::create([
            'user_id' => $user->id,
            'name' => 'My Camera',
            'desired_config' => ['jpeg_quality' => 15, 'frame_size' => 'VGA'],
            'current_config' => ['jpeg_quality' => 20, 'frame_size' => 'QVGA'],
            'desired_config_version' => 2,
            'current_config_version' => 1,
            'last_config_status' => 'Pending',
        ]);

        $response = $this->actingAs($user)->getJson("/api/cameras/{$camera->id}/config");

        $response->assertStatus(200)
            ->assertJson([
                'camera_id' => $camera->id,
                'camera_name' => 'My Camera',
                'desired_config' => ['jpeg_quality' => 15, 'frame_size' => 'VGA'],
                'current_config' => ['jpeg_quality' => 20, 'frame_size' => 'QVGA'],
                'desired_config_version' => 2,
                'current_config_version' => 1,
                'last_config_status' => 'Pending',
            ]);
    }

    public function test_can_update_camera_config(): void
    {
        $user = User::factory()->create();
        $camera = Camera::create([
            'user_id' => $user->id,
            'name' => 'My Camera',
            'device_id' => 'device-uuid-1234',
            'is_active' => true,
        ]);

        // Mock EMQX Service to prevent external network calls during publish
        $mockEmqx = $this->mock(\App\Services\EmqxService::class);
        $mockEmqx->shouldReceive('publish')->andReturn(true);

        $payload = [
            'jpeg_quality' => 15,
            'frame_size' => 'VGA',
            'capture_interval_ms' => 3000,
            'telemetry_interval_ms' => 5000,
            'mqtt_buffer' => 32768,
            'image_enabled' => true,
            'telemetry_enabled' => true,
            'ota_enabled' => true,
        ];

        $response = $this->actingAs($user)->putJson("/api/cameras/{$camera->id}/config", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Configuration command queued successfully.',
                'desired_config_version' => 1,
            ]);

        $camera->refresh();
        $this->assertEquals(15, $camera->desired_config['jpeg_quality']);
        $this->assertEquals('VGA', $camera->desired_config['frame_size']);
        $this->assertEquals(1, $camera->desired_config_version);
    }

    public function test_can_reboot_camera(): void
    {
        $user = User::factory()->create();
        $camera = Camera::create([
            'user_id' => $user->id,
            'name' => 'My Camera',
            'device_id' => 'device-uuid-1234',
            'is_active' => true,
        ]);

        $mockEmqx = $this->mock(\App\Services\EmqxService::class);
        $mockEmqx->shouldReceive('publish')->andReturn(true);

        $response = $this->actingAs($user)->postJson("/api/cameras/{$camera->id}/reboot");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Reboot command initiated successfully.',
            ]);
    }

    public function test_can_trigger_manual_capture(): void
    {
        $user = User::factory()->create();
        $camera = Camera::create([
            'user_id' => $user->id,
            'name' => 'My Camera',
            'device_id' => 'device-uuid-1234',
            'is_active' => true,
        ]);

        $mockEmqx = $this->mock(\App\Services\EmqxService::class);
        $mockEmqx->shouldReceive('publish')
            ->once()
            ->with("ws/camera/device-uuid-1234/config", ['action' => 'capture'])
            ->andReturn(true);

        $response = $this->actingAs($user)->postJson("/api/cameras/{$camera->id}/capture");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Manual capture command sent successfully.',
            ]);
    }
}

