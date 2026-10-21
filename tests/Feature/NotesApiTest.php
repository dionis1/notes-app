<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_read_notes(): void
    {
        $this->getJson('/api/v1/notes')->assertUnauthorized();
    }

    public function test_owner_can_create_filter_update_and_delete_note(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $response = $this->postJson('/api/v1/notes', [
            'title' => 'First idea', 'content' => 'Keep it private.', 'is_important' => true,
        ])->assertCreated()->assertJsonPath('data.version', 1);

        $id = $response->json('data.id');
        $this->getJson('/api/v1/notes?important=1&q=First')->assertOk()->assertJsonCount(1, 'data');
        $this->patchJson('/api/v1/notes/'.$id, [
            'title' => 'Better idea', 'version' => 1,
        ])->assertOk()->assertJsonPath('data.version', 2);

        $this->patchJson('/api/v1/notes/'.$id, [
            'title' => 'Stale idea', 'version' => 1,
        ])->assertStatus(409);
        $this->deleteJson('/api/v1/notes/'.$id, ['version' => 1])->assertStatus(409);
        $this->assertDatabaseHas('notes', ['id' => $id, 'title' => 'Better idea', 'user_id' => $user->id]);
        $this->deleteJson('/api/v1/notes/'.$id, ['version' => 2])->assertNoContent();
        $this->assertDatabaseCount('notes', 0);
    }

    public function test_other_users_and_unowned_legacy_notes_are_never_visible(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $private = Note::factory()->create(['user_id' => $owner->id]);
        Note::factory()->create(['user_id' => null]);

        Sanctum::actingAs($other);
        $this->getJson('/api/v1/notes')->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/notes/'.$private->id)->assertNotFound();
        $this->patchJson('/api/v1/notes/'.$private->id, ['title' => 'Not mine', 'version' => 1])->assertNotFound();
        $this->deleteJson('/api/v1/notes/'.$private->id, ['version' => 1])->assertNotFound();
    }

    public function test_validation_rejects_invalid_notes_and_unbounded_pagination(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/v1/notes', ['title' => '', 'content' => ''])
            ->assertUnprocessable()->assertJsonValidationErrors(['title', 'content']);
        $this->getJson('/api/v1/notes?per_page=51')
            ->assertUnprocessable()->assertJsonValidationErrors('per_page');
    }
}
