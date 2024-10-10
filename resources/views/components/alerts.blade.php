
<script>
    function confirmDelete(onConfirm) {
        Swal.fire({
            title: '¿Esta seguro?',
            text: 'Una vez eliminado no podra recuperar el registro',
            icon: 'warning',
            showClass: {
                backdrop: 'swal2-noanimation', // disable backdrop animation
                popup: '', // disable popup animation
                icon: '' // disable icon animation
            },
            hideClass: {
                backdrop: 'swal2-noanimation', // disable backdrop animation
                popup: '', // disable popup animation
                icon: '' // disable icon animation
            },
            showCancelButton: true,
            focusCancel: true,
            confirmButtonText: 'aceptar',
            cancelButtonText: 'cancelar',
        }).then((result) => {
            if (result.isConfirmed && typeof onConfirm == 'function') {
                try {
                    onConfirm();
                } catch (error) {}
            }
        });
    }


    function swalAlert(title, text, icon) {
        Swal.fire({
            title: title || '',
            text: text || '',
            icon: icon || 'info',
            showClass: {
                backdrop: 'swal2-noanimation', // disable backdrop animation
                popup: '',                     // disable popup animation
                icon: ''                       // disable icon animation
            },
            hideClass: {
                backdrop: 'swal2-noanimation', // disable backdrop animation
                popup: '',                     // disable popup animation
                icon: ''                       // disable icon animation
            },
        });
    }


    function swalToast(message, icon = 'success', position = 'top', timer = 1500) {
    Swal.fire({
        toast: true,
        position: position,
        icon: icon,
        title: message,
        showConfirmButton: false,
        timer: timer,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });
}
</script>
