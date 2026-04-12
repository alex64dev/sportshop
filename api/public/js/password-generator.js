document.addEventListener('DOMContentLoaded', function() {
    const passwordInputs = document.querySelectorAll('input[name*="[password]"], input[name*="[plainPassword]"]');

    passwordInputs.forEach(input => {
        if (input.closest('.password-generator-enhanced')) return;

        const wrapper = document.createElement('div');
        wrapper.className = 'input-group password-generator-enhanced';

        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);

        const btnHtml = `
            <button type="button" class="btn btn-outline-secondary" onclick="generatePwd(this)" title="Générer un mot de passe fort">
                <i class="fas fa-key"></i>
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="togglePwd(this)" title="Afficher/Masquer">
                <i class="fas fa-eye"></i>
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="copyPwd(this)" title="Copier dans le presse-papier">
                <i class="fas fa-copy"></i>
            </button>
        `;

        input.insertAdjacentHTML('afterend', btnHtml);

        const strength = document.createElement('small');
        strength.className = 'password-strength d-block mt-1';
        wrapper.parentNode.insertBefore(strength, wrapper.nextSibling);

        input.addEventListener('input', () => checkStrength(input));
        checkStrength(input);
    });
});

function generatePwd(btn) {
    const input = btn.closest('.input-group').querySelector('input');
    const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=';
    let pwd = '';
    for(let i = 0; i < 16; i++) pwd += chars[Math.floor(Math.random() * chars.length)];
    input.value = pwd;

    input.type = 'text';
    const eyeIcon = btn.parentNode.querySelector('.fa-eye, .fa-eye-slash');
    if(eyeIcon) {
        eyeIcon.className = 'fas fa-eye-slash';
    }

    checkStrength(input);
    input.dispatchEvent(new Event('input', { bubbles: true }));
}

function togglePwd(btn) {
    const input = btn.closest('.input-group').querySelector('input');
    const icon = btn.querySelector('i');

    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

function copyPwd(btn) {
    const input = btn.closest('.input-group').querySelector('input');
    if (!input.value) return;

    navigator.clipboard.writeText(input.value).then(() => {
        const icon = btn.querySelector('i');
        const orig = icon.className;
        icon.className = 'fas fa-check text-success';
        setTimeout(() => icon.className = orig, 1500);
    });
}

function checkStrength(input) {
    const pwd = input.value;
    const wrapper = input.closest('.input-group');
    const div = wrapper ? wrapper.nextElementSibling : null;

    if (!div || !div.classList.contains('password-strength')) return;

    if (!pwd) { div.innerHTML = ''; return; }

    let s = 0;
    if(pwd.length >= 8) s++;
    if(pwd.length >= 12) s++;
    if(/[a-z]/.test(pwd)) s++;
    if(/[A-Z]/.test(pwd)) s++;
    if(/[0-9]/.test(pwd)) s++;
    if(/[^a-zA-Z0-9]/.test(pwd)) s++;

    div.innerHTML = s <= 2 ? '<span class="text-danger"><i class="fas fa-times-circle"></i> Faible</span>' :
        s <= 4 ? '<span class="text-warning"><i class="fas fa-exclamation-circle"></i> Moyen</span>' :
            '<span class="text-success"><i class="fas fa-check-circle"></i> Fort</span>';
}
