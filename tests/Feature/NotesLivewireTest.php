<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Note;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NotesLivewireTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function can_create_a_note()
    {
        Livewire::test('notes')
            ->set('title', 'Test Note')
            ->set('content', 'This is a test note')
            ->set('is_important', true)
            ->call('save')
            ->assertDispatched('message', 'Note created');

        $this->assertDatabaseHas('notes', [
            'title' => 'Test Note',
            'content' => 'This is a test note',
            'is_important' => true,
        ]);
    }

    /** @test */
    public function can_update_a_note()
    {
        $note = Note::factory()->create();

        Livewire::test('notes')
            ->set('noteId', $note->id)
            ->set('title', 'Updated Title')
            ->set('content', 'Updated Content')
            ->call('save')
            ->assertDispatched('message', 'Note updated');

        $this->assertDatabaseHas('notes', [
            'id' => $note->id,
            'title' => 'Updated Title',
            'content' => 'Updated Content',
        ]);
    }

    /** @test */
    public function can_delete_a_note()
    {
        $note = Note::factory()->create();

        Livewire::test('notes')
            ->call('delete', $note->id)
            ->assertDispatched('message', 'Note deleted');

        $this->assertDatabaseMissing('notes', ['id' => $note->id]);
    }
}
