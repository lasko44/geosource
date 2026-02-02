<?php

namespace Tests\Feature;

use App\Models\CitationCheck;
use App\Models\CitationQuery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CitationQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_citation_query_can_be_created(): void
    {
        $user = User::factory()->create(['token_balance' => 100]);

        $response = $this->actingAs($user)->post('/citations/queries', [
            'query' => 'What is the best SEO tool?',
            'domain' => 'example.com',
            'brand' => 'Example Brand',
            'frequency' => 'manual',
        ]);

        $this->assertDatabaseHas('citation_queries', [
            'user_id' => $user->id,
            'query' => 'What is the best SEO tool?',
            'domain' => 'example.com',
        ]);
    }

    public function test_user_can_view_citation_query_details(): void
    {
        $user = User::factory()->create(['token_balance' => 100]);

        $query = CitationQuery::factory()->create([
            'user_id' => $user->id,
            'query' => 'What is the best SEO tool?',
            'domain' => 'example.com',
        ]);

        $response = $this->actingAs($user)->get("/citations/queries/{$query->uuid}");

        $response->assertOk();
    }

    public function test_user_cannot_access_another_users_citation_query(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $query = CitationQuery::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($user)->get("/citations/queries/{$query->uuid}");

        $response->assertStatus(403);
    }

    public function test_citation_check_status_endpoint(): void
    {
        $user = User::factory()->create(['token_balance' => 100]);

        $query = CitationQuery::factory()->create([
            'user_id' => $user->id,
        ]);

        $check = CitationCheck::factory()->create([
            'citation_query_id' => $query->id,
            'user_id' => $user->id,
            'status' => 'completed',
            'is_cited' => true,
        ]);

        $response = $this->actingAs($user)->get("/citations/checks/{$check->uuid}/status");

        $response->assertOk()
            ->assertJson([
                'status' => 'completed',
                'is_cited' => true,
            ]);
    }

    public function test_user_can_delete_citation_query(): void
    {
        $user = User::factory()->create(['token_balance' => 100]);

        $query = CitationQuery::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->delete("/citations/queries/{$query->uuid}");

        $response->assertRedirect();
        $this->assertSoftDeleted($query);
    }

    public function test_citation_check_creates_pending_check(): void
    {
        $user = User::factory()->create(['token_balance' => 10]);

        $query = CitationQuery::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->post("/citations/queries/{$query->uuid}/check", [
            'platform' => 'perplexity',
        ]);

        $this->assertDatabaseHas('citation_checks', [
            'citation_query_id' => $query->id,
            'user_id' => $user->id,
            'platform' => 'perplexity',
        ]);
    }

    public function test_citation_check_requires_valid_platform(): void
    {
        $user = User::factory()->create(['token_balance' => 100]);

        $query = CitationQuery::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->post("/citations/queries/{$query->uuid}/check", [
            'platform' => 'invalid-platform',
        ]);

        $response->assertSessionHasErrors('platform');
    }

    public function test_citation_query_can_be_updated(): void
    {
        $user = User::factory()->create(['token_balance' => 100]);

        $query = CitationQuery::factory()->create([
            'user_id' => $user->id,
            'query' => 'Original query',
        ]);

        $response = $this->actingAs($user)->put("/citations/queries/{$query->uuid}", [
            'query' => 'Updated query',
            'domain' => $query->domain,
            'frequency' => 'manual',
        ]);

        $response->assertRedirect();

        $query->refresh();
        $this->assertEquals('Updated query', $query->query);
    }

    public function test_citation_trends_page_accessible(): void
    {
        $user = User::factory()->create(['token_balance' => 100]);

        $response = $this->actingAs($user)->get('/citations/trends');

        $this->assertTrue(in_array($response->status(), [200, 302]));
    }

    public function test_citation_alerts_page_accessible(): void
    {
        $user = User::factory()->create(['token_balance' => 100]);

        $response = $this->actingAs($user)->get('/citations/alerts');

        $this->assertTrue(in_array($response->status(), [200, 302]));
    }
}
