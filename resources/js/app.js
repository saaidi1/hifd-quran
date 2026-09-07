import './bootstrap';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';

document.addEventListener('DOMContentLoaded', function () {
    const offcanvasToggle = document.getElementById('sidebarToggler');
    const sidebar = document.getElementById('appSidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (offcanvasToggle && sidebar) {
        offcanvasToggle.addEventListener('click', () => {
            sidebar.classList.toggle('show');
            if (overlay) {
                overlay.classList.toggle('d-none');
            }
        });
    }

    if (overlay) {
        overlay.addEventListener('click', () => {
            sidebar?.classList.remove('show');
            overlay.classList.add('d-none');
        });
    }
});
