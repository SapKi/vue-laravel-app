<?php

namespace Tests\Feature;

use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemApiTest extends TestCase
{
    use RefreshDatabase;

    // --- POST /api/items ---

    public function test_can_submit_an_item(): void
    {
        $response = $this->postJson('/api/items', [
            'title'   => 'My first report',
            'content' => 'This is a normal piece of content with no issues.',
        ]);

        $response->assertStatus(201)
                 ->assertJsonFragment(['title' => 'My first report'])
                 ->assertJsonFragment(['status' => 'pending'])
                 ->assertJsonStructure(['id', 'title', 'content', 'status', 'risk_score', 'flags', 'suggested_action']);
    }

    public function test_submit_requires_title_and_content(): void
    {
        $this->postJson('/api/items', [])->assertStatus(422)->assertJsonValidationErrors(['title', 'content']);
        $this->postJson('/api/items', ['title' => 'x'])->assertStatus(422)->assertJsonValidationErrors(['content']);
    }

    public function test_moderation_flags_spam_content(): void
    {
        $response = $this->postJson('/api/items', [
            'title'   => 'BUY NOW LIMITED OFFER',
            'content' => 'Click here to earn money fast and win a prize guaranteed!',
        ]);

        $response->assertStatus(201);
        $data = $response->json();

        $this->assertGreaterThan(0, $data['risk_score']);
        $this->assertContains('spam', $data['flags']);
        $this->assertEquals('reject', $data['suggested_action']);
    }

    public function test_moderation_assigns_low_risk_to_clean_content(): void
    {
        $response = $this->postJson('/api/items', [
            'title'   => 'Feedback about the product',
            'content' => 'I really enjoyed using this product. The interface is intuitive and the performance is great.',
        ]);

        $response->assertStatus(201);
        $data = $response->json();

        $this->assertEquals(0, $data['risk_score']);
        $this->assertEquals('approve', $data['suggested_action']);
    }

    // --- GET /api/items ---

    public function test_can_list_items(): void
    {
        Item::factory()->count(3)->create();

        $this->getJson('/api/items')->assertStatus(200)->assertJsonCount(3);
    }

    public function test_can_filter_items_by_status(): void
    {
        Item::factory()->create(['status' => 'pending']);
        Item::factory()->create(['status' => 'approved']);
        Item::factory()->create(['status' => 'rejected']);

        $this->getJson('/api/items?status=pending')->assertStatus(200)->assertJsonCount(1);
        $this->getJson('/api/items?status=approved')->assertStatus(200)->assertJsonCount(1);
    }

    public function test_can_search_items_by_title(): void
    {
        Item::factory()->create(['title' => 'Unique search term here']);
        Item::factory()->create(['title' => 'Something else']);

        $this->getJson('/api/items?search=Unique')
             ->assertStatus(200)
             ->assertJsonCount(1)
             ->assertJsonFragment(['title' => 'Unique search term here']);
    }

    // --- GET /api/items/{id} ---

    public function test_can_fetch_single_item(): void
    {
        $item = Item::factory()->create();

        $this->getJson("/api/items/{$item->id}")
             ->assertStatus(200)
             ->assertJsonFragment(['id' => $item->id]);
    }

    public function test_returns_404_for_missing_item(): void
    {
        $this->getJson('/api/items/9999')->assertStatus(404);
    }

    // --- PATCH /api/items/{id}/review ---

    public function test_can_approve_a_pending_item(): void
    {
        $item = Item::factory()->create(['status' => 'pending']);

        $this->patchJson("/api/items/{$item->id}/review", [
            'status' => 'approved',
            'note'   => 'Looks good to me.',
        ])->assertStatus(200)
          ->assertJsonFragment(['status' => 'approved'])
          ->assertJsonFragment(['reviewer_note' => 'Looks good to me.']);
    }

    public function test_can_reject_a_pending_item_without_note(): void
    {
        $item = Item::factory()->create(['status' => 'pending']);

        $this->patchJson("/api/items/{$item->id}/review", ['status' => 'rejected'])
             ->assertStatus(200)
             ->assertJsonFragment(['status' => 'rejected']);
    }

    public function test_cannot_review_an_already_reviewed_item(): void
    {
        $item = Item::factory()->create(['status' => 'approved']);

        $this->patchJson("/api/items/{$item->id}/review", ['status' => 'rejected'])
             ->assertStatus(422)
             ->assertJsonFragment(['message' => 'Item has already been reviewed.']);
    }

    public function test_review_requires_valid_status(): void
    {
        $item = Item::factory()->create(['status' => 'pending']);

        $this->patchJson("/api/items/{$item->id}/review", ['status' => 'maybe'])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['status']);
    }

    // --- DELETE /api/items/{id} ---

    public function test_can_delete_an_item(): void
    {
        $item = Item::factory()->create();

        $this->deleteJson("/api/items/{$item->id}")->assertStatus(204);
        $this->assertDatabaseMissing('items', ['id' => $item->id]);
    }

    public function test_delete_returns_404_for_missing_item(): void
    {
        $this->deleteJson('/api/items/9999')->assertStatus(404);
    }
}
