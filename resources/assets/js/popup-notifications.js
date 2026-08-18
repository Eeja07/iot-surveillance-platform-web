/**
 * Global Popup Notification Manager for Mivion IoT Surveillance
 * Manages user preference for popup toast alerts with localStorage persistence.
 */
(function () {
    'use strict';

    const STORAGE_KEY = 'mivion_popup_notifications_enabled';

    /**
     * Check if popup notifications are enabled. Default is true.
     * @returns {boolean}
     */
    window.isPopupNotificationEnabled = function () {
        const stored = localStorage.getItem(STORAGE_KEY);
        return stored === null ? true : stored === 'true';
    };

    /**
     * Set popup notifications state.
     * @param {boolean} enabled
     */
    window.setPopupNotifications = function (enabled) {
        localStorage.setItem(STORAGE_KEY, enabled ? 'true' : 'false');
        window.updateNotificationUI();
        window.dispatchEvent(new CustomEvent('popupNotificationChanged', { detail: { enabled: !!enabled } }));
    };

    /**
     * Toggle popup notifications state and display confirmation toast.
     */
    window.togglePopupNotifications = function () {
        const nextState = !window.isPopupNotificationEnabled();
        window.setPopupNotifications(nextState);

        // Show brief confirmation toast
        const bs = window.bootstrap || (typeof bootstrap !== 'undefined' ? bootstrap : null);
        if (bs && bs.Toast) {
            let toastContainer = document.querySelector('.toast-container');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
                toastContainer.style.zIndex = '1100';
                document.body.appendChild(toastContainer);
            }

            const toastId = 'toast-notif-toggle-' + Date.now();
            const headerBg = nextState ? 'bg-success' : 'bg-secondary';
            const icon = nextState ? 'ti-bell' : 'ti-bell-off';
            const statusLabel = nextState ? 'Notifikasi Pop-up Aktif' : 'Notifikasi Pop-up Dinonaktifkan';
            const statusDesc = nextState
                ? 'Pop-up toast saat kamera mendeteksi orang akan ditampilkan.'
                : 'Pop-up toast deteksi telah dibisukan (muted).';

            const toastHTML = `
                <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="toast-header ${headerBg} text-white">
                        <i class="ti ${icon} me-2"></i>
                        <strong class="me-auto">${statusLabel}</strong>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body">
                        ${statusDesc}
                    </div>
                </div>
            `;

            toastContainer.insertAdjacentHTML('beforeend', toastHTML);
            const toastEl = document.getElementById(toastId);
            if (toastEl) {
                const toast = new bs.Toast(toastEl, { delay: 3500 });
                toast.show();
                toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
            }
        }
    };

    /**
     * Sync UI elements (icons, badges, switches, status texts) with current state.
     */
    window.updateNotificationUI = function () {
        const enabled = window.isPopupNotificationEnabled();

        // Update Bell Icons
        const bellIcons = document.querySelectorAll('.notification-bell-icon');
        bellIcons.forEach(icon => {
            if (enabled) {
                icon.className = 'ti ti-bell ti-md notification-bell-icon text-body';
            } else {
                icon.className = 'ti ti-bell-off ti-md notification-bell-icon text-muted';
            }
        });

        // Update Badge Dots
        const badgeDots = document.querySelectorAll('.notification-badge-dot');
        badgeDots.forEach(dot => {
            if (enabled) {
                dot.className = 'badge bg-success badge-dot position-absolute top-0 end-0 mt-1 me-1 notification-badge-dot';
                dot.style.display = '';
            } else {
                dot.className = 'badge bg-secondary badge-dot position-absolute top-0 end-0 mt-1 me-1 notification-badge-dot';
                dot.style.display = '';
            }
        });

        // Update Toggle Switches
        const toggleInputs = document.querySelectorAll('.popup-notification-toggle-input');
        toggleInputs.forEach(input => {
            input.checked = enabled;
        });

        // Update Status Badges/Texts
        const statusTexts = document.querySelectorAll('.popup-notification-status-text');
        statusTexts.forEach(txt => {
            txt.textContent = enabled ? 'Aktif' : 'Muted';
            txt.className = enabled
                ? 'badge bg-label-success ms-1 popup-notification-status-text'
                : 'badge bg-label-secondary ms-1 popup-notification-status-text';
        });
    };

    /**
     * Show a test notification popup for previewing.
     */
    window.testPopupNotification = function () {
        const bs = window.bootstrap || (typeof bootstrap !== 'undefined' ? bootstrap : null);
        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            toastContainer.style.zIndex = '1100';
            document.body.appendChild(toastContainer);
        }

        const toastId = 'toast-test-' + Date.now();
        const toastHTML = `
            <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header bg-primary text-white">
                    <i class="ti ti-bell-ringing me-2"></i>
                    <strong class="me-auto">Uji Pop-up Notifikasi</strong>
                    <small class="text-white">Baru saja</small>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    <strong>Demo Kamera 01</strong><br>
                    Pop-up notifikasi realtime berfungsi dengan baik.
                </div>
            </div>
        `;

        toastContainer.insertAdjacentHTML('beforeend', toastHTML);
        const toastEl = document.getElementById(toastId);
        if (toastEl && bs && bs.Toast) {
            const toast = new bs.Toast(toastEl, { delay: 4000 });
            toast.show();
            toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
        }
    };

    // Initialize UI on DOM Ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', window.updateNotificationUI);
    } else {
        window.updateNotificationUI();
    }
})();
