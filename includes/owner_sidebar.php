<?php
/**
 * Animal Owner Sidebar Navigation Component
 * Veterinary Care for Animal Medical Solution
 */
$activePage = $activePage ?? '';
$user = current_user();
?>
<div class="sidebar-backdrop"></div>
<aside class="sidebar-wrapper">
    <div class="sidebar-header">
        <a href="<?= url('owner/dashboard.php') ?>" class="brand-title text-decoration-none">
            <span class="brand-icon d-inline-flex align-items-center justify-content-center text-white" style="width: 32px; height: 32px; background: #0d9488; border-radius: 8px;">
                <i class="bi bi-heart-pulse-fill"></i>
            </span>
            <span>VetCare <span class="badge bg-primary-subtle text-primary fw-semibold px-2 py-1" style="font-size: 0.7rem;">Owner</span></span>
        </a>
    </div>

    <div class="sidebar-nav">
        <div class="nav-section-title">Core Operations</div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= $activePage === 'dashboard' ? 'active' : '' ?>" href="<?= url('owner/dashboard.php') ?>">
                    <i class="bi bi-grid-fill"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activePage === 'animals' ? 'active' : '' ?>" href="<?= url('owner/animals.php') ?>">
                    <i class="bi bi-shield-heart-fill"></i> My Animals
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activePage === 'animal_add' ? 'active' : '' ?>" href="<?= url('owner/animal_add.php') ?>">
                    <i class="bi bi-plus-circle-fill"></i> Register Animal
                </a>
            </li>
        </ul>

        <div class="nav-section-title">Appointments & Visits</div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= $activePage === 'appointment_add' ? 'active' : '' ?>" href="<?= url('owner/appointment_add.php') ?>">
                    <i class="bi bi-calendar-plus-fill"></i> Book Appointment
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activePage === 'appointments' ? 'active' : '' ?>" href="<?= url('owner/appointments.php') ?>">
                    <i class="bi bi-calendar-check-fill"></i> My Appointments
                </a>
            </li>
        </ul>

        <div class="nav-section-title">Health & Medical</div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= $activePage === 'medical_history' ? 'active' : '' ?>" href="<?= url('owner/medical_history.php') ?>">
                    <i class="bi bi-file-earmark-medical-fill"></i> Medical Records
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activePage === 'vaccinations' ? 'active' : '' ?>" href="<?= url('owner/vaccinations.php') ?>">
                    <i class="bi bi-eyedropper"></i> Vaccinations
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activePage === 'followups' ? 'active' : '' ?>" href="<?= url('owner/followups.php') ?>">
                    <i class="bi bi-bell-fill"></i> Follow-up Reminders
                </a>
            </li>
        </ul>

        <div class="nav-section-title">Account</div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= $activePage === 'profile' ? 'active' : '' ?>" href="<?= url('owner/profile.php') ?>">
                    <i class="bi bi-person-circle"></i> My Profile
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= url('index.php') ?>" target="_blank">
                    <i class="bi bi-box-arrow-up-right"></i> Visit Website
                </a>
            </li>
        </ul>
    </div>

    <div class="sidebar-footer">
        <div class="d-flex align-items-center justify-content-between">
            <div class="user-mini-card">
                <div class="user-avatar-circle">
                    <?= strtoupper(substr($user['full_name'] ?? 'U', 0, 1)) ?>
                </div>
                <div class="overflow-hidden">
                    <div class="text-white small fw-bold text-truncate" style="max-width: 130px;"><?= e($user['full_name'] ?? 'Owner') ?></div>
                    <div class="text-muted text-truncate" style="font-size: 0.75rem; max-width: 130px;"><?= e($user['email'] ?? '') ?></div>
                </div>
            </div>
            <form action="<?= url('auth/logout.php') ?>" method="POST" class="m-0">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-sm btn-outline-danger p-1 px-2 border-0" title="Sign Out">
                    <i class="bi bi-box-arrow-right fs-5"></i>
                </button>
            </form>
        </div>
    </div>
</aside>
