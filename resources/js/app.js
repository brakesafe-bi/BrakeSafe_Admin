import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const initAuthForm = (selector) => {
        const form = document.getElementById(selector);
        if (!form) return;

        const submitButton = form.querySelector('button[type="submit"]');
        form.addEventListener('submit', () => {
            if (!submitButton) return;
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="inline-flex items-center gap-2"><svg class="w-5 h-5 animate-spin" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>Processing...</span>';
        });
    };

    initAuthForm('login-form');
    initAuthForm('register-form');
    initAuthForm('otp-form');

    const sanitizeValue = (value) => {
        if (typeof value !== 'string') return value;
        return value.replace(/<[^>]*>/g, '').trim();
    };

    const clearFormValues = (form) => {
        if (!form) return;
        form.querySelectorAll('input, textarea').forEach((field) => {
            if (!field.name) return;
            if (['button', 'submit', 'reset', 'hidden', 'checkbox', 'radio', 'file'].includes(field.type)) return;
            field.value = '';
        });
        form.querySelectorAll('select').forEach((select) => {
            select.selectedIndex = 0;
        });
        const methodInput = form.querySelector('input[name="_method"]');
        if (methodInput) {
            methodInput.remove();
        }
    };

    const sanitizeFormValues = (form) => {
        if (!form) return;
        form.querySelectorAll('input, textarea').forEach((field) => {
            if (!field.name) return;
            if (['button', 'submit', 'reset', 'hidden', 'checkbox', 'radio', 'file'].includes(field.type)) return;
            field.value = sanitizeValue(field.value);
        });
    };

    const setupAddModal = (buttonId, modalId, formId, titleId, submitButtonId, addTitle) => {
        const button = document.getElementById(buttonId);
        const modal = document.getElementById(modalId);
        const form = document.getElementById(formId);
        const title = document.getElementById(titleId);
        const submitButton = document.getElementById(submitButtonId);
        if (!button || !modal || !form) return;

        button.addEventListener('click', () => {
            clearFormValues(form);
            const storeAction = form.dataset.storeAction;
            if (storeAction) {
                form.action = storeAction;
            }
            if (title) {
                title.textContent = addTitle;
            }
            if (submitButton) {
                submitButton.textContent = 'Save';
            }
            modal.classList.remove('hidden');
        });

        form.addEventListener('submit', () => {
            sanitizeFormValues(form);
        });
    };

    setupAddModal('open-device-modal', 'device-modal', 'device-form', 'device-modal-title', 'device-modal-submit', 'Add Device');
    setupAddModal('open-vehicle-modal', 'vehicle-modal', 'vehicle-form', 'vehicle-modal-title', 'vehicle-modal-submit', 'Register Vehicle');

    const attachSweetAlertConfirm = () => {
        document.querySelectorAll('form[data-sweetalert-confirm]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (form.__sweetalertConfirmed) {
                    form.__sweetalertConfirmed = false;
                    return;
                }

                event.preventDefault();

                const title = form.dataset.swalTitle || 'Are you sure?';
                const text = form.dataset.swalText || 'This action cannot be undone.';
                const confirmButtonText = form.dataset.swalConfirmButtonText || 'Yes, delete it';
                const cancelButtonText = form.dataset.swalCancelButtonText || 'Cancel';

                Swal.fire({
                    title,
                    text,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText,
                    cancelButtonText,
                    reverseButtons: true,
                    customClass: {
                        confirmButton: 'btn-primary',
                        cancelButton: 'btn-secondary',
                    },
                    buttonsStyling: false,
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.__sweetalertConfirmed = true;
                        form.submit();
                    }
                });
            });
        });
    };

    attachSweetAlertConfirm();

    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const target = document.getElementById(button.dataset.passwordToggle);
            if (!target) return;
            const isPassword = target.type === 'password';
            target.type = isPassword ? 'text' : 'password';
            const label = button.querySelector('span');
            if (label) {
                label.textContent = isPassword ? 'Hide' : 'Show';
            }
        });
    });

    const notificationToggle = document.getElementById('notification-toggle');
    const notificationPanel = document.getElementById('notification-panel');

    if (notificationToggle && notificationPanel) {
        const isPanelVisible = () => notificationPanel.style.display === 'block';

        notificationToggle.addEventListener('click', (event) => {
            event.stopPropagation();
            notificationPanel.style.display = isPanelVisible() ? 'none' : 'block';
            notificationToggle.setAttribute('aria-expanded', String(!isPanelVisible()));
        });

        document.addEventListener('click', (event) => {
            if (!notificationPanel.contains(event.target) && !notificationToggle.contains(event.target)) {
                notificationPanel.style.display = 'none';
                notificationToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    const sidebar = document.querySelector('aside');
    const overlay = document.getElementById('sidebar-overlay');
    document.getElementById('menu-toggle')?.addEventListener('click', () => {
        sidebar?.classList.toggle('-translate-x-full');
        overlay?.classList.toggle('hidden');
    });

    overlay?.addEventListener('click', () => {
        sidebar?.classList.add('-translate-x-full');
        overlay?.classList.add('hidden');
    });

});
