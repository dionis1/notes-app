<div>
    <section class="page-heading"><div><p class="eyebrow">A LITTLE CLARITY, EVERY DAY</p><h1>Your notes</h1><p class="muted">Capture an idea. Keep what matters.</p></div><span class="count">{{ $notes->total() }} {{ $filterImportant ? 'important' : 'saved' }} notes</span></section>
    <div role="status" aria-live="polite">@if(session('message'))<p class="notice">{{ session('message') }}</p>@endif</div>
    <div wire:offline class="error" role="alert">You are offline. Your changes have not been saved.</div>
    @error('conflict') <p class="error" role="alert">{{ $message }}</p> @enderror
    <div class="workspace">
        <section class="card editor" aria-labelledby="editor-title">
            <div class="section-heading"><h2 id="editor-title">{{ $noteId ? 'Edit note' : 'New note' }}</h2><span class="muted">{{ $noteId ? 'Make it yours' : 'Start with a thought' }}</span></div>
            <form wire:submit="save">
                <label for="title">Title</label><input id="title" wire:model="title" maxlength="255" aria-describedby="title-error" placeholder="Give your thought a title">
                <p id="title-error" class="error">@error('title'){{ $message }}@enderror</p>
                <label for="content">Your note <span class="muted">/ up to 10,000 characters</span></label>
                <textarea id="content" wire:model="content" maxlength="10000" rows="9" aria-describedby="content-error" placeholder="What's on your mind?"></textarea>
                <p id="content-error" class="error">@error('content'){{ $message }}@enderror</p>
                <label class="checkbox"><input type="checkbox" wire:model="is_important"> Mark as important</label>
                <div class="form-actions"><button class="primary" type="submit" wire:loading.attr="disabled" wire:target="save">{{ $noteId ? 'Save changes' : 'Save note' }}</button><button type="button" class="quiet" wire:click="resetForm">{{ $noteId ? 'Cancel editing' : 'Clear' }}</button><span wire:loading wire:target="save" role="status">Saving…</span></div>
            </form>
        </section>
        <section aria-label="Saved notes">
            <div class="list-toolbar"><h2>Your collection</h2><button class="filter" wire:click="toggleFilter" aria-pressed="{{ $filterImportant ? 'true' : 'false' }}">{{ $filterImportant ? 'Show all notes' : 'Important only' }}</button></div>
            <ul class="notes-list">
                @forelse($notes as $note)
                <li wire:key="note-{{ $note->id }}" class="card note {{ $note->is_important ? 'important' : '' }}">
                    <div class="note-meta"><time datetime="{{ $note->created_at->toIso8601String() }}">{{ $note->created_at->format('M j, Y') }}</time>@if($note->is_important)<span class="badge">★ Important</span>@endif</div>
                    <h3>{{ $note->title }}</h3><p class="note-content">{{ $note->content }}</p>
                    <div class="note-actions"><button class="quiet" wire:click="edit({{ $note->id }})" aria-label="Edit {{ $note->title }}">Edit</button><button class="danger quiet" wire:click="delete({{ $note->id }}, {{ $note->version }})" wire:confirm="Delete this note permanently?" wire:loading.attr="disabled" aria-label="Delete {{ $note->title }}">Delete</button></div>
                </li>
                @empty <li class="card empty"><span class="empty-symbol">✎</span><h3>{{ $filterImportant ? 'No important notes yet' : 'Room for your next idea' }}</h3><p class="muted">{{ $filterImportant ? 'Mark a note as important to find it here.' : 'Create your first note using the form.' }}</p></li>
                @endforelse
            </ul>
            {{ $notes->links('components.pagination') }}
        </section>
    </div>
</div>
