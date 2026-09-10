document.addEventListener('alpine:init', () => {
    Alpine.store('sidebar', {
        open: false,
        toggle() {
            this.open = !this.open;
        },
    });
});
