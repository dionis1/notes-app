<?php

namespace Tests\Feature;

use App\Livewire\Notes;
use App\Models\Note;
use App\Services\NoteService;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class NotesLivewireMockTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function can_create_note_with_mock()
    {
        $mockService = Mockery::mock(NoteService::class);

        // Called on mount() + after save()
        $mockService->shouldReceive('getAllNotes')
            ->twice()
            ->andReturn(collect([]));

        // Save behavior
        $mockService->shouldReceive('saveNote')
            ->once()
            ->with(null, [
                'title' => 'Mock Note',
                'content' => 'This is a mock note',
                'is_important' => true,
            ])
            ->andReturn('Note created');

        $this->app->instance(NoteService::class, $mockService);

        Livewire::test(Notes::class)
            ->set('title', 'Mock Note')
            ->set('content', 'This is a mock note')
            ->set('is_important', true)
            ->call('save')
            ->assertDispatched('message', 'Note created');
    }

    /** @test */
    public function can_delete_note_with_mock()
    {
        $mockService = Mockery::mock(NoteService::class);

        // Called on mount() + after delete()
        $mockService->shouldReceive('getAllNotes')
            ->twice()
            ->andReturn(collect([]));

        // Delete behavior
        $mockService->shouldReceive('deleteNote')
            ->once()
            ->with(1)
            ->andReturn('Note deleted');

        $this->app->instance(NoteService::class, $mockService);

        Livewire::test(Notes::class)
            ->call('delete', 1)
            ->assertDispatched('message', 'Note deleted');
    }

    /** @test */
    public function can_edit_note_with_mock()
    {
        $mockNote = new Note([
            'title' => 'Existing Note',
            'content' => 'This is an existing note',
            'is_important' => true,
        ]);
        $mockNote->id = 1; 

        $mockService = Mockery::mock(NoteService::class);

        $mockService->shouldReceive('getAllNotes')
            ->once()
            ->andReturn(collect([]));

        $mockService->shouldReceive('findNote')
            ->once()
            ->with(1)
            ->andReturn($mockNote);

        $this->app->instance(NoteService::class, $mockService);

        Livewire::test(Notes::class)
            ->call('edit', 1)
            ->assertSet('title', 'Existing Note')
            ->assertSet('content', 'This is an existing note')
            ->assertSet('is_important', true)
            ->assertSet('noteId', 1);
    }


}
