// ─── PWA REGISTRATION ───────────────────────────────────
// Include this on any page that should be installable.
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
}

// Capture the browser's install prompt so we can trigger it from our own
// button instead of waiting for the browser's default mini-infobar.
let _deferredInstallPrompt = null;

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    _deferredInstallPrompt = e;
    document.querySelectorAll('.pwa-install-btn').forEach((btn) => {
        btn.style.display = 'inline-flex';
    });
});

function triggerPwaInstall() {
    if (!_deferredInstallPrompt) return;
    _deferredInstallPrompt.prompt();
    _deferredInstallPrompt.userChoice.finally(() => {
        _deferredInstallPrompt = null;
        document.querySelectorAll('.pwa-install-btn').forEach((btn) => {
            btn.style.display = 'none';
        });
    });
}// Hide the install button if the app is already installed/running standalone
window.addEventListener('appinstalled', () => {
    document.querySelectorAll('.pwa-install-btn').forEach((btn) => {
        btn.style.display = 'none';
    });
});

// Hide the install button if the app is already installed/running standalone
window.addEventListener('appinstalled', () => {
    document.querySelectorAll('.pwa-install-btn').forEach((btn) => {
        btn.style.display = 'none';
    });
});

