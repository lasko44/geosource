<?php

namespace Tests\Feature;

use App\Models\Scan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScanTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_view_another_users_scan(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $scan = Scan::factory()->create([
            'user_id' => $otherUser->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($user)->get("/scans/{$scan->uuid}");

        $response->assertStatus(403);
    }

    public function test_scan_can_be_deleted(): void
    {
        $user = User::factory()->create();

        $scan = Scan::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($user)->delete("/scans/{$scan->uuid}");

        $response->assertRedirect();
        $this->assertSoftDeleted($scan);
    }

    public function test_scan_status_endpoint(): void
    {
        $user = User::factory()->create();

        $scan = Scan::factory()->processing()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get("/scans/{$scan->uuid}/status");

        $response->assertOk()
            ->assertJson([
                'status' => 'processing',
            ]);
    }

    public function test_user_can_view_scan_history(): void
    {
        $user = User::factory()->create();

        Scan::factory()->count(3)->create([
            'user_id' => $user->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($user)->get('/scans');

        $response->assertOk();
    }

    public function test_user_can_view_completed_scan(): void
    {
        $user = User::factory()->create();

        $scan = Scan::factory()->create([
            'user_id' => $user->id,
            'url' => 'https://example.com',
            'status' => 'completed',
            'score' => 75.5,
            'grade' => 'B',
        ]);

        $response = $this->actingAs($user)->get("/scans/{$scan->uuid}");

        $response->assertOk();
    }
}
