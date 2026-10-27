<?php
/**
 * Veterinarian Sidebar Navigation Component
 * Veterinary Care for Animal Medical Solution
 */
$activePage = $activePage ?? '';
$user = current_user();
?>
<div class="sidebar-backdrop"></div>
<aside class="sidebar-wrapper">
    <div class="sidebar-header">
        <a href="<?= url('veterinarian/dashboard.php') ?>" class="brand-title text-decoration-none">
            <span class="brand-icon d-inline-flex align-items-center justify-content-center text-white" style="width: 32px; height: 32px; background: #0284c7; border-radius: 8px;">
                <i class="bi bi-hospital"></i>
            </span>
            <span>VetCare <span class="badge bg-info-subtle text-info-emphasis fw-semibold px-2 py-1" style="font-size: 0.7rem;">Veterinarian</span></span>
        </a>
    </div>

    <div class="sidebar-nav">
        <div class="nav-section-title">Clinical Practice</div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= $activePage === 'dashboard' ? 'active' : '' ?>" href="<?= url('veterinarian/dashboard.php') ?>">
                    <i class="bi bi-grid-fill"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activePage === 'appointments' ? 'active' : '' ?>" href="<?= url('veterinarian/appointments.php') ?>">
                    <i class="bi bi-calendar2-range-fill"></i> Appointment Requests
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activePage === 'availability' ? 'active' : '' ?>" href="<?= url('veterinarian/availability.php') ?>">
                    <i class="bi bi-clock-history"></i> Manage Availability
                </a>
            </li>
        </ul>

        <div class="nav-section-title">Patient Care & Records</div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= $activePage === 'my_patients' ? 'active' : '' ?>" href="<?= url('veterinarian/my_patients.php') ?>">
                    <i class="bi bi-folder-heart"></i> Patient Directory
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activePage === 'medical_history' ? 'active' : '' ?>" href="<?= url('veterinarian/medical_history.php') ?>">
                    <i class="bi bi-clipboard2-pulse-fill"></i> Medical Records
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activePage === 'medical_record_add' ? 'active' : '' ?>" href="<?= url('veterinarian/medical_record_add.php') ?>">
                    <i class="bi bi-plus-square-fill"></i> New Consultation Record
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activePage === 'vaccination_add' ? 'active' : '' ?>" href="<?= url('veterinarian/vaccination_add.php') ?>">
                    <i class="bi bi-eyedropper"></i> Record Vaccination
                </a>
            </li>
        </ul>

        <div class="nav-section-title">Account & Profile</div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= $activePage === 'profile' ? 'active' : '' ?>" href="<?= url('veterinarian/profile.php') ?>">
                    <i class="bi bi-person-badge-fill"></i> Professional Profile
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
                <div class="user-avatar-circle" style="background: #0284c7;">
                    <?= strtoupper(substr($user['full_name'] ?? 'V', 0, 1)) ?>
                </div>
                <div class="overflow-hidden">
                    <div class="text-white small fw-bold text-truncate" style="max-width: 130px;"><?= e($user['full_name'] ?? 'Doctor') ?></div>
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
