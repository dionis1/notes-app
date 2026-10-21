<?php

namespace Tests\Feature;

use App\Livewire\Notes;
use App\Models\Note;
use App\Models\User;
use App\Services\NoteService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\TestCase;

class NotesLivewireTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
        $this->actingAs($this->owner);
    }

    private function note(array $attributes = []): Note
    {
        return Note::factory()->create(['user_id' => $this->owner->id] + $attributes);
    }

    public function test_create_edit_and_delete_note(): void
    {
        $component = Livewire::test(Notes::class)->set('title', '  First idea  ')->set('content', 'A thought')->set('is_important', true)->call('save')->assertHasNoErrors();
        $note = Note::firstOrFail();
        $this->assertSame($this->owner->id, $note->user_id);
        $this->assertSame('First idea', $note->title);
        $component->call('edit', $note->id)->assertSet('title', 'First idea')->set('title', 'Updated idea')->call('save')->assertHasNoErrors();
        $this->assertSame(2, $note->fresh()->version);
        $component->call('delete', $note->id, 2)->assertHasNoErrors();
        $this->assertModelMissing($note);
    }

    public function test_invalid_fields_and_reset_clear_errors(): void
    {
        Livewire::test(Notes::class)->set('title', '   ')->set('content', '   ')->call('save')->assertHasErrors(['title' => 'required', 'content' => 'required'])
            ->call('resetForm')->assertHasNoErrors()->set('title', str_repeat('x', 256))->set('content', str_repeat('x', 10001))->call('save')->assertHasErrors(['title' => 'max', 'content' => 'max']);
        $this->assertDatabaseCount('notes', 0);
    }

    public function test_maximum_lengths_are_accepted(): void
    {
        Livewire::test(Notes::class)->set('title', str_repeat('x', 255))->set('content', str_repeat('x', 10000))->call('save')->assertHasNoErrors();
        $this->assertDatabaseCount('notes', 1);
    }

    public function test_only_owned_notes_are_visible_and_filterable(): void
    {
        $this->note(['title' => 'My important idea', 'is_important' => true]);
        $this->note(['title' => 'My ordinary idea', 'is_important' => false]);
        Note::factory()->create(['title' => 'Private other user']);
        Note::factory()->create(['user_id' => null, 'title' => 'Legacy anonymous']);
        Livewire::test(Notes::class)->assertSee('My ordinary idea')->assertDontSee('Private other user')->assertDontSee('Legacy anonymous')
            ->call('toggleFilter')->assertSee('My important idea')->assertDontSee('My ordinary idea')->call('toggleFilter')->assertSee('My ordinary idea');
    }

    public function test_other_users_cannot_edit_or_delete_notes(): void
    {
        $other = Note::factory()->create();
        Livewire::test(Notes::class)->call('edit', $other->id)->assertNotFound();
        Livewire::test(Notes::class)->call('delete', $other->id, $other->version)->assertNotFound();
        $this->assertModelExists($other);
        $this->assertFalse($this->owner->can('update', $other));
        $this->assertFalse($this->owner->can('delete', $other));
    }

    public function test_forged_updates_cannot_change_another_users_note(): void
    {
        $other = Note::factory()->create();
        try {
            app(NoteService::class)->save($this->owner, $other->id, 1, ['title' => 'forged']);
            $this->fail('A cross-user update must fail');
        } catch (ModelNotFoundException) {
            $this->assertNotSame('forged', $other->fresh()->title);
        }
    }

    public function test_sensitive_state_is_locked(): void
    {
        $this->expectException(CannotUpdateLockedPropertyException::class);
        Livewire::test(Notes::class)->set('noteId', $this->note()->id);
    }

    public function test_concurrent_edit_preserves_draft_and_prevents_overwrite(): void
    {
        $note = $this->note();
        $first = Livewire::test(Notes::class)->call('edit', $note->id);
        $second = Livewire::test(Notes::class)->call('edit', $note->id);
        $first->set('title', 'First writer')->call('save');
        $second->set('title', 'My unsaved draft')->call('save')->assertHasErrors('conflict')->assertSet('title', 'My unsaved draft');
        $this->assertSame('First writer', $note->fresh()->title);
    }

    public function test_deleted_note_does_not_get_recreated(): void
    {
        $note = $this->note();
        $component = Livewire::test(Notes::class)->call('edit', $note->id);
        $note->delete();
        $component->set('title', 'Preserved draft')->call('save')->assertHasErrors('conflict')->assertSet('title', 'Preserved draft');
        $this->assertDatabaseCount('notes', 0);
    }

    public function test_stale_delete_cannot_remove_a_newer_version(): void
    {
        $note = $this->note();
        app(NoteService::class)->save($this->owner, $note->id, 1, ['title' => 'New version']);
        Livewire::test(Notes::class)->call('delete', $note->id, 1)->assertHasErrors('conflict');
        $this->assertModelExists($note);
    }

    public function test_deleting_edited_note_resets_form(): void
    {
        $note = $this->note();
        Livewire::test(Notes::class)->call('edit', $note->id)->call('delete', $note->id, 1)->assertSet('noteId', null)->assertSet('title', '');
    }

    public function test_pagination_is_bounded_and_filter_resets_page(): void
    {
        Note::factory()->count(11)->create(['user_id' => $this->owner->id, 'created_at' => now(), 'is_important' => true]);
        $last = Note::orderByDesc('id')->first();
        $first = Note::orderBy('id')->first();
        Livewire::test(Notes::class)->assertSee($last->title)->assertDontSee($first->title)->call('nextPage')->assertSee($first->title)
            ->call('toggleFilter')->assertSee($last->title)->assertDontSee($first->title);
    }

    public function test_html_is_escaped(): void
    {
        $this->note(['title' => '<script>alert(1)</script>', 'content' => '<img src=x onerror=alert(1)>']);
        Livewire::test(Notes::class)->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_guest_cannot_call_component(): void
    {
        auth()->logout();
        Livewire::test(Notes::class)->assertForbidden();
    }
}
