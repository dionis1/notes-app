document.addEventListener('livewire:init', () => {
    Livewire.hook('request', ({ fail }) => {
        fail(({ status, preventDefault }) => {
            if (status === 419) return; // Preserve Livewire's session-expiry recovery prompt.
            preventDefault();
            const error = document.getElementById('request-error');
            error.textContent = 'The request could not be completed. Your draft is still here. Check your connection and try again.';
            error.hidden = false;
        });
    });
    Livewire.on('editing', () => document.getElementById('title')?.focus());
});
