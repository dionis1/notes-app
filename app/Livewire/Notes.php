<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\NoteService;
use App\Repositories\Interfaces\NoteRepositoryInterface;

class Notes extends Component
{
    public $notes = [];
    public $title = '';
    public $content = '';
    public $is_important = false;
    public $noteId = null;
    public $filterImportant = false;

    protected $rules = [
        'title' => 'required|string|max:255',
        'content' => 'required|string',
        'is_important' => 'boolean',
    ];

    protected NoteService $service;

    public function __construct()
    {
        $this->service = app(NoteService::class);
    }

    public function mount()
    {
        $this->loadNotes();
    }

    public function loadNotes()
    {
        $this->notes = $this->service->getAllNotes($this->filterImportant);
    }

    public function toggleFilter()
    {
        $this->filterImportant = !$this->filterImportant;
        $this->loadNotes();
    }

     
    public function edit($id)
    {
            $note = $this->service->findNote($id);

            if (!$note) {
                return;
            }

            $this->noteId = $note->id;
            $this->title = $note->title;
            $this->content = $note->content;
            $this->is_important = $note->is_important;
    }



    public function save()
    {
        $data = $this->validate();

        $message = $this->service->saveNote($this->noteId, $data);

        session()->flash('message', $message);
        $this->dispatch('message', $message);

        $this->resetForm();
        $this->loadNotes();
    }

    public function delete($id)
    {
        $message = $this->service->deleteNote($id);
        session()->flash('message', $message);
        $this->dispatch('message', $message);
        $this->loadNotes();
    }

    public function resetForm()
    {
        $this->reset(['title', 'content', 'is_important', 'noteId']);
    }

    public function render()
    {
        return view('livewire.notes')->layout('components.layouts.app');
    }
}
