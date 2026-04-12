document.addEventListener('DOMContentLoaded', () => {
    document.body.addEventListener('change', (event) => {
        const toggle = event.target;

        if (toggle.matches('.js-toggle input[type="checkbox"]')) {
            toggle.disabled = true;
            toggle.classList.add('ea-toggle-disabled');

            const label = toggle.closest('label');
            if (label) label.style.opacity = '0.6';

            setTimeout(() => {
                window.location.reload();
            }, 100);
        }
    });
});
