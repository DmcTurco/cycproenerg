@if (session('success') || session('error'))
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            Swal.fire({
                icon: '{{ session('success') ? 'success' : 'error' }}',
                title: @json(session('success') ?? session('error')),
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3500,
                timerProgressBar: true,
            });
        });
    </script>
@endif
