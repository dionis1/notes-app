@if($paginator->hasPages())
<nav class="pagination" aria-label="Notes pages">
    <button class="quiet" wire:click="previousPage" @disabled($paginator->onFirstPage())>Previous</button>
    <span>Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>
    <button class="quiet" wire:click="nextPage" @disabled(!$paginator->hasMorePages())>Next</button>
</nav>
@endif
