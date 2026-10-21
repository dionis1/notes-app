<?php

namespace App\Livewire;

use App\Models\User;
use App\Services\NoteService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class Notes extends Component
{
    use WithPagination;

    public string $title = '';

    public string $content = '';

    public bool $is_important = false;

    public bool $filterImportant = false;

    #[Locked]
    public ?int $noteId = null;

    #[Locked]
    public ?int $version = null;

    protected NoteService $service;

    protected function rules(): array
    {
        return ['title' => 'required|string|max:255', 'content' => 'required|string|max:10000', 'is_important' => 'boolean'];
    }

    public function boot(NoteService $service): void
    {
        $this->service = $service;
    }

    protected function user(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }

    public function toggleFilter(): void
    {
        $this->filterImportant = ! $this->filterImportant;
        $this->resetPage();
    }

    public function edit(int $id): void
    {
        try {
            $note = $this->service->find($this->user(), $id);
        } catch (ModelNotFoundException) {
            abort(404);
        }
        $this->authorize('update', $note);
        $this->resetValidation();
        $this->noteId = $note->id;
        $this->version = $note->version;
        $this->title = $note->title;
        $this->content = $note->content;
        $this->is_important = $note->is_important;
        $this->dispatch('editing');
    }

    public function save(): void
    {
        $this->title = trim($this->title);
        $this->content = trim($this->content);
        $data = $this->validate();
        try {
            $this->service->save($this->user(), $this->noteId, $this->version, $data);
        } catch (ModelNotFoundException) {
            $this->addError('conflict', 'This note no longer exists. Your draft is preserved; cancel to create a new note.');

            return;
        }
        $message = $this->noteId ? 'Note updated' : 'Note created';
        $this->resetForm();
        $this->resetPage();
        session()->flash('message', $message);
    }

    public function delete(int $id, int $version): void
    {
        try {
            $this->service->delete($this->user(), $id, $version);
        } catch (ModelNotFoundException) {
            abort(404);
        }
        if ($this->noteId === $id) {
            $this->resetForm();
        }
        $this->resetPage();
        session()->flash('message', 'Note deleted');
    }

    public function resetForm(): void
    {
        $this->reset(['title', 'content', 'is_important', 'noteId', 'version']);
        $this->resetValidation();
    }

    public function render()
    {
        $notes = $this->user()->notes()->when($this->filterImportant, fn ($query) => $query->where('is_important', true))
            ->orderByDesc('created_at')->orderByDesc('id')->paginate(10);

        return view('livewire.notes', ['notes' => $notes])->layout('components.layouts.app');
    }
}
