/**
 * Main JavaScript - AI Online Exam & Proctoring System
 * Tahap 14: UI/UX Polish Enhancements
 */

document.addEventListener('DOMContentLoaded', function () {
    const sidebarToggleBtn = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('appSidebar');
    const overlay = document.getElementById('sidebarOverlay');

    // 1. Sidebar Toggle
    if (sidebarToggleBtn && sidebar) {
        sidebarToggleBtn.addEventListener('click', function (e) {
            e.preventDefault();
            sidebar.classList.toggle('show');
            if (overlay) {
                overlay.classList.toggle('show');
            }
        });
    }

    // Close sidebar when clicking overlay on mobile
    if (overlay && sidebar) {
        overlay.addEventListener('click', function () {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
    }

    // 2. Initialize Bootstrap tooltips & popovers
    if (typeof bootstrap !== 'undefined') {
        if (bootstrap.Tooltip) {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.forEach(function (tooltipTriggerEl) {
                new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
        if (bootstrap.Popover) {
            const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            popoverTriggerList.forEach(function (popoverTriggerEl) {
                new bootstrap.Popover(popoverTriggerEl);
            });
        }
    }

    // 3. Form Loading States (Prevent double submission & provide visual feedback)
    const forms = document.querySelectorAll('form:not(.no-auto-loading)');
    forms.forEach(function (form) {
        form.addEventListener('submit', function (e) {
            // If HTML5 validation fails, don't show loading spinner
            if (!form.checkValidity()) {
                return;
            }

            const submitBtn = form.querySelector('button[type="submit"]:not(.no-loading)');
            if (submitBtn) {
                // Short timeout so default form submission event is dispatched first
                setTimeout(function () {
                    submitBtn.classList.add('is-loading');
                    submitBtn.setAttribute('disabled', 'disabled');
                }, 10);
            }
        });
    });

    // 4. Auto-dismiss flash alerts after 6 seconds (if present)
    const autoAlerts = document.querySelectorAll('.alert-dismissible:not(.alert-permanent)');
    autoAlerts.forEach(function (alertEl) {
        setTimeout(function () {
            if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                const bsAlert = bootstrap.Alert.getInstance(alertEl) || new bootstrap.Alert(alertEl);
                if (bsAlert) {
                    bsAlert.close();
                }
            } else {
                alertEl.style.transition = 'opacity 0.5s ease';
                alertEl.style.opacity = '0';
                setTimeout(() => alertEl.remove(), 500);
            }
        }, 6000);
    });

    // 5. In-Website Notification System (Navbar Bell & Live Counter)
    initNotificationSystem();
});

function initNotificationSystem() {
    const badge = document.getElementById('navNotifBadge');
    const listContainer = document.getElementById('navNotifList');
    const markAllBtn = document.getElementById('btnMarkAllReadNav');

    if (!badge && !listContainer) {
        return; // User is guest or navbar not present
    }

    // Determine Base URL
    const baseUrl = (typeof window.BASE_URL !== 'undefined') ? window.BASE_URL : window.location.origin + (window.location.pathname.startsWith('/Project2') ? '/Project2' : '');

    function fetchNotifications() {
        fetch(baseUrl + '/notification/recent', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) throw new Error('Network error');
            return response.json();
        })
        .then(data => {
            if (!data || !data.success) return;

            // Update unread badge
            const count = parseInt(data.unread_count, 10) || 0;
            if (badge) {
                if (count > 0) {
                    badge.textContent = count > 99 ? '99+' : count;
                    badge.style.display = 'inline-block';
                } else {
                    badge.style.display = 'none';
                }
            }

            // Update dropdown list
            if (listContainer) {
                const notifs = data.notifications || [];
                if (notifs.length === 0) {
                    listContainer.innerHTML = `
                        <div class="p-3 text-center text-muted text-xs">
                            <i class="bi bi-bell-slash text-secondary d-block fs-4 mb-1"></i>
                            Belum ada notifikasi
                        </div>
                    `;
                } else {
                    let html = '';
                    notifs.forEach(n => {
                        const isRead = parseInt(n.is_read, 10) === 1;
                        const icon = {
                            'exam_published': 'bi-file-earmark-plus text-primary',
                            'exam_schedule': 'bi-calendar-event text-warning',
                            'exam_cancel': 'bi-x-octagon text-danger',
                            'exam_result': 'bi-trophy text-success',
                            'exam_reminder': 'bi-alarm text-info',
                            'system': 'bi-gear text-secondary'
                        }[n.category] || 'bi-bell text-primary';

                        const targetUrl = n.link_url ? (n.link_url.startsWith('http') ? n.link_url : baseUrl + '/' + n.link_url.replace(/^\//, '')) : baseUrl + '/notification';

                        html += `
                            <a href="${targetUrl}" class="list-group-item list-group-item-action p-2 text-decoration-none border-bottom ${!isRead ? 'bg-light' : ''}">
                                <div class="d-flex align-items-start gap-2">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 mt-1" style="width: 28px; height: 28px; background: #f1f5f9;">
                                        <i class="bi ${icon} text-xs"></i>
                                    </div>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="fw-semibold text-xs text-dark text-truncate" style="max-width: 190px;">${escapeHtml(n.title)}</div>
                                            ${!isRead ? '<span class="badge bg-primary" style="font-size: 0.55rem;">Baru</span>' : ''}
                                        </div>
                                        <div class="text-xs text-muted text-truncate" style="max-width: 220px;">${escapeHtml(n.message)}</div>
                                    </div>
                                </div>
                            </a>
                        `;
                    });
                    listContainer.innerHTML = html;
                }
            }
        })
        .catch(() => {
            // Fail silently on connectivity loss
        });
    }

    // Mark all read handler inside dropdown
    if (markAllBtn) {
        markAllBtn.addEventListener('click', function (e) {
            e.preventDefault();
            fetch(baseUrl + '/notification/markAllAsRead', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(() => {
                if (badge) badge.style.display = 'none';
                fetchNotifications();
            })
            .catch(() => {
                window.location.href = baseUrl + '/notification/markAllAsRead';
            });
        });
    }

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // Initial load
    fetchNotifications();

    // Live background polling every 45 seconds
    setInterval(fetchNotifications, 45000);
}

/**
 * Helper to copy text to clipboard with tooltip or visual feedback
 */
function copyToClipboard(text, triggerBtn) {
    if (!navigator.clipboard) {
        const tempInput = document.createElement('input');
        tempInput.value = text;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand('copy');
        document.body.removeChild(tempInput);
    } else {
        navigator.clipboard.writeText(text);
    }

    if (triggerBtn) {
        const origHTML = triggerBtn.innerHTML;
        triggerBtn.innerHTML = '<i class="bi bi-check2 text-success"></i> Disalin!';
        setTimeout(function () {
            triggerBtn.innerHTML = origHTML;
        }, 2000);
    }
}
