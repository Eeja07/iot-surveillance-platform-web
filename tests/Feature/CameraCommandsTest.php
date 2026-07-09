<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Camera;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CameraCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_commands_endpoint_requires_authentication(): void
    {
        $this->postJson('/api/cameras/1/commands', ['command' => 'restart'])
            ->assertStatus(401);
    }

    public function test_cannot_send_commands_to_other_users_camera(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $camera = Camera::create([
            'user_id' => $otherUser->id,
            'name' => 'Other Camera',
        ]);

        $this->actingAs($user)
            ->postJson("/api/cameras/{$camera->id}/commands", ['command' => 'restart'])
            ->assertStatus(403);
    }

    public function test_can_send_restart_command_successfully(): void
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

        $response = $this->actingAs($user)
            ->postJson("/api/cameras/{$camera->id}/commands", ['command' => 'restart']);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Command sent successfully.',
            ]);
    }

    public function test_can_send_camera_reinit_command_successfully(): void
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

        $response = $this->actingAs($user)
            ->postJson("/api/cameras/{$camera->id}/commands", ['command' => 'camera_reinit']);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Command sent successfully.',
            ]);
    }

    public function test_invalid_command_returns_validation_error(): void
    {
        $user = User::factory()->create();
        $camera = Camera::create([
            'user_id' => $user->id,
            'name' => 'My Camera',
            'device_id' => 'device-uuid-1234',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/cameras/{$camera->id}/commands", ['command' => 'invalid_command']);

        $response->assertStatus(422);
    }
}
