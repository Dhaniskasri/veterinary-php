/**
 * Veterinary Care for Animal Medical Solution - Main JavaScript
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Mobile Sidebar Toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarWrapper = document.querySelector('.sidebar-wrapper');
    const backdrop = document.querySelector('.sidebar-backdrop');

    if (sidebarToggle && sidebarWrapper) {
        sidebarToggle.addEventListener('click', function () {
            sidebarWrapper.classList.toggle('show');
            if (backdrop) backdrop.classList.toggle('show');
        });
    }

    if (backdrop) {
        backdrop.addEventListener('click', function () {
            sidebarWrapper.classList.remove('show');
            backdrop.classList.remove('show');
        });
    }

    // 2. Auto-dismiss alerts after 6 seconds
    const alerts = document.querySelectorAll('.flash-messages-container .alert');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) {
                bsAlert.close();
            }
        }, 6000);
    });

    // 3. Confirm Delete / Cancel actions
    const confirmButtons = document.querySelectorAll('[data-confirm]');
    confirmButtons.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            const message = btn.getAttribute('data-confirm') || 'Are you sure you want to proceed?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });

    // 4. Password Toggle
    const togglePassButtons = document.querySelectorAll('.toggle-password-btn');
    togglePassButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const targetId = btn.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = btn.querySelector('i');
            if (input) {
                if (input.type === 'password') {
                    input.type = 'text';
                    if (icon) {
                        icon.classList.remove('bi-eye');
                        icon.classList.add('bi-eye-slash');
                    }
                } else {
                    input.type = 'password';
                    if (icon) {
                        icon.classList.remove('bi-eye-slash');
                        icon.classList.add('bi-eye');
                    }
                }
            }
        });
    });

    // 5. Dynamic Slot Fetching in Appointment Booking Form
    const vetSelect = document.getElementById('veterinarian_id');
    const dateInput = document.getElementById('appointment_date');
    const slotsContainer = document.getElementById('availableSlotsContainer');
    const slotsLoading = document.getElementById('slotsLoading');

    function loadAvailableSlots() {
        if (!vetSelect || !dateInput || !slotsContainer) return;

        const vetId = vetSelect.value;
        const dateVal = dateInput.value;

        if (!vetId || !dateVal) {
            slotsContainer.innerHTML = '<div class="text-muted small p-3 bg-light rounded-3 text-center">Please select a veterinarian and date to view available appointment slots.</div>';
            return;
        }

        if (slotsLoading) slotsLoading.classList.remove('d-none');
        slotsContainer.innerHTML = '';

        // Derive base path from window location or data attribute
        const appBase = document.body.getAttribute('data-base-url') || '';
        const fetchUrl = `${appBase}/actions/get_slots.php?vet_id=${encodeURIComponent(vetId)}&date=${encodeURIComponent(dateVal)}`;

        fetch(fetchUrl)
            .then(res => res.json())
            .then(data => {
                if (slotsLoading) slotsLoading.classList.add('d-none');

                if (!data.success) {
                    slotsContainer.innerHTML = `<div class="alert alert-warning small mb-0">${data.message || 'Unable to fetch slots.'}</div>`;
                    return;
                }

                if (!data.slots || data.slots.length === 0) {
                    slotsContainer.innerHTML = `
                        <div class="alert alert-info small mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-calendar-x fs-5"></i>
                            <div>No available slots found for this date. The veterinarian may not have working hours configured or all slots may be booked. Please select another date.</div>
                        </div>
                    `;
                    return;
                }

                let html = '<div class="row g-2">';
                data.slots.forEach((slot, idx) => {
                    const isBooked = slot.is_booked;
                    const radioId = `slot_${idx}`;
                    html += `
                        <div class="col-6 col-sm-4 col-md-3">
                            <input type="radio" class="btn-check" name="appointment_time" id="${radioId}" value="${slot.time}" ${isBooked ? 'disabled' : ''} required>
                            <label class="btn ${isBooked ? 'btn-outline-secondary opacity-50' : 'btn-outline-primary'} w-100 py-2 small fw-medium" for="${radioId}">
                                ${slot.formatted_time}
                                ${isBooked ? '<span class="d-block text-danger x-small" style="font-size: 0.72rem;">Booked</span>' : '<span class="d-block text-success x-small" style="font-size: 0.72rem;">Available</span>'}
                            </label>
                        </div>
                    `;
                });
                html += '</div>';
                slotsContainer.innerHTML = html;
            })
            .catch(err => {
                if (slotsLoading) slotsLoading.classList.add('d-none');
                console.error(err);
                slotsContainer.innerHTML = '<div class="alert alert-danger small mb-0">Error checking availability slots. Please try again.</div>';
            });
    }

    if (vetSelect && dateInput) {
        vetSelect.addEventListener('change', loadAvailableSlots);
        dateInput.addEventListener('change', loadAvailableSlots);

        // Run once if pre-selected
        if (vetSelect.value && dateInput.value) {
            loadAvailableSlots();
        }
    }
});
