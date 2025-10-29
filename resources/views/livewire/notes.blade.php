<div class="space-y-6">

    <!-- Flash Message -->
    @if(session()->has('message'))
        <div class="p-3 bg-green-100 text-green-800 rounded shadow">
            {{ session('message') }}
        </div>
    @endif

    <!-- Filter -->
    <div class="flex items-center gap-3">
        <button wire:click="toggleFilter" class="px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded">
            {{ $filterImportant ? 'Show All' : 'Show Important' }}
        </button>
        <span class="ml-auto text-gray-600">Count: {{ count($notes) }}</span>
    </div>

    <!-- Form -->
    <form wire:submit.prevent="save" class="space-y-3 bg-white p-4 rounded shadow">
        <input wire:model.defer="title" type="text" placeholder="Title" class="w-full border rounded p-2">
        @error('title') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror

        <textarea wire:model.defer="content" placeholder="Content" class="w-full border rounded p-2"></textarea>
        @error('content') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror

        <label class="inline-flex items-center gap-2">
            <input type="checkbox" wire:model="is_important"> Important
        </label>

        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded">
                {{ $noteId ? 'Update' : 'Add' }}
            </button>
            <button type="button" wire:click="resetForm" class="px-3 py-2 border rounded hover:bg-gray-100">Reset</button>
        </div>
    </form>

    <!-- Notes List -->
    <ul class="space-y-3">
        @forelse($notes as $note)
            <li class="bg-white p-4 rounded shadow flex justify-between flex-wrap {{ $note->is_important ? 'border-l-4 border-yellow-400' : '' }}">
                <div>
                    <h2 class="font-semibold text-lg">{{ $note->title }}</h2>
                    <p class="text-gray-700">{{ $note->content }}</p>
                    <small class="text-gray-400">{{ $note->created_at->format('d.m.Y H:i') }}</small>
                </div>
                <div class="flex gap-2 mt-2 sm:mt-0">
                    <button wire:click="edit({{ $note->id }})" class="text-blue-500 hover:text-blue-700">✏️</button>
                    <button wire:click="delete({{ $note->id }})" class="text-red-500 hover:text-red-700">🗑️</button>
                </div>
            </li>
        @empty
            <li class="text-gray-500">No notes found.</li>
        @endforelse
    </ul>

</div>
