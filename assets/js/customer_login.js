function switchTab(tab) {
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    const tabs = document.querySelectorAll('.auth-tab');

    if (tab === 'login') {
        loginForm.style.display = 'block';
        registerForm.style.display = 'none';
        tabs[0].classList.add('active');
        tabs[1].classList.remove('active');
    } else {
        loginForm.style.display = 'none';
        registerForm.style.display = 'block';
        tabs[0].classList.remove('active');
        tabs[1].classList.add('active');
    }
}

function togglePassword(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);

    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}
