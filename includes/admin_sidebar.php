<?php
/**
 * Administrator Sidebar Navigation Component
 * Veterinary Care for Animal Medical Solution
 */
$activePage = $activePage ?? '';
$user = current_user();
?>
<div class="sidebar-backdrop"></div>
<aside class="sidebar-wrapper">
    <div class="sidebar-header">
        <a href="<?= url('admin/dashboard.php') ?>" class="brand-title text-decoration-none">
            <span class="brand-icon d-inline-flex align-items-center justify-content-center text-white" style="width: 32px; height: 32px; background: #e11d48; border-radius: 8px;">
                <i class="bi bi-shield-lock-fill"></i>
            </span>
            <span>VetCare <span class="badge bg-danger-subtle text-danger fw-semibold px-2 py-1" style="font-size: 0.7rem;">Admin</span></span>
        </a>
    </div>

    <div class="sidebar-nav">
        <div class="nav-section-title">Overview & Analytics</div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= $activePage === 'dashboard' ? 'active' : '' ?>" href="<?= url('admin/dashboard.php') ?>">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activePage === 'reports' ? 'active' : '' ?>" href="<?= url('admin/reports.php') ?>">
                    <i class="bi bi-bar-chart-line-fill"></i> System Reports
                </a>
            </li>
        </ul>

        <div class="nav-section-title">User Management</div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= $activePage === 'owners' ? 'active' : '' ?>" href="<?= url('admin/owners.php') ?>">
                    <i class="bi bi-people-fill"></i> Animal Owners
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activePage === 'veterinarians' ? 'active' : '' ?>" href="<?= url('admin/veterinarians.php') ?>">
                    <i class="bi bi-person-lines-fill"></i> Veterinarians & Approvals
                </a>
            </li>
        </ul>

        <div class="nav-section-title">System Records</div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= $activePage === 'animals' ? 'active' : '' ?>" href="<?= url('admin/animals.php') ?>">
                    <i class="bi bi-collection-fill"></i> All Animals
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activePage === 'appointments' ? 'active' : '' ?>" href="<?= url('admin/appointments.php') ?>">
                    <i class="bi bi-calendar-event-fill"></i> All Appointments
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activePage === 'medical_records' ? 'active' : '' ?>" href="<?= url('admin/medical_records.php') ?>">
                    <i class="bi bi-journal-medical"></i> Medical Records
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activePage === 'vaccinations' ? 'active' : '' ?>" href="<?= url('admin/vaccinations.php') ?>">
                    <i class="bi bi-eyedropper"></i> Vaccinations
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activePage === 'contact_messages' ? 'active' : '' ?>" href="<?= url('admin/contact_messages.php') ?>">
                    <i class="bi bi-envelope-paper-fill"></i> Inbound Messages
                </a>
            </li>
        </ul>

        <div class="nav-section-title">Configuration</div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= $activePage === 'settings' ? 'active' : '' ?>" href="<?= url('admin/settings.php') ?>">
                    <i class="bi bi-gear-fill"></i> Website & Settings
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
                <div class="user-avatar-circle" style="background: #e11d48;">
                    A
                </div>
                <div class="overflow-hidden">
                    <div class="text-white small fw-bold text-truncate" style="max-width: 130px;"><?= e($user['full_name'] ?? 'Admin') ?></div>
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
