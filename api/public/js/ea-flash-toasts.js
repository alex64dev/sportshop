(function () {
    function showFlashesAsToasts() {
        const flashContainer = document.querySelector('#flash-messages');
        if (!flashContainer) return;

        let toastContainer = document.querySelector('.ea-toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'ea-toast-container toast-container position-fixed top-0 end-0 p-3';
            toastContainer.style.zIndex = '20000';
            document.body.appendChild(toastContainer);
        }

        flashContainer.querySelectorAll('.alert').forEach((flash) => {
            const type =
                flash.classList.contains('alert-success')
                    ? 'success'
                    : flash.classList.contains('alert-danger') || flash.classList.contains('alert-error')
                        ? 'danger'
                        : flash.classList.contains('alert-warning')
                            ? 'warning'
                            : 'info';

            const message = flash.textContent.trim();

            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-bg-${type} border-0 shadow-lg mb-2`;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');
            toast.innerHTML = `
        <div class="d-flex">
          <div class="toast-body fw-semibold">${message}</div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Fermer"></button>
        </div>
      `;

            toastContainer.appendChild(toast);
            const toastBootstrap = bootstrap.Toast.getOrCreateInstance(toast, { delay: 4000 });
            toastBootstrap.show();
        });

        flashContainer.remove();
    }

    document.addEventListener('DOMContentLoaded', showFlashesAsToasts);
    document.addEventListener('ea.page-load', showFlashesAsToasts);
})();
