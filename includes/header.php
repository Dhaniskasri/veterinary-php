<?php
/**
 * Master Header Component
 * Veterinary Care for Animal Medical Solution
 */
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/init.php';
}

$pageTitle = $pageTitle ?? APP_NAME;
$isDashboard = $isDashboard ?? false;
$activeNav = $activeNav ?? '';
$currentUser = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= e(APP_SHORT_NAME) ?></title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="Veterinary Care for Animal Medical Solution - Comprehensive healthcare management, appointment booking, medical history, and vaccination tracking for animals.">
    <meta name="keywords" content="veterinary, animal health, pet care, vet appointments, vaccination records, pet medical history">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS & Icons CDN with fallback support -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Chart.js CDN for Dashboards & Reports -->
    <?php if ($isDashboard || $activeNav === 'reports'): ?>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    <?php endif; ?>

    <!-- Custom Theme Stylesheets -->
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
    <?php if ($isDashboard): ?>
        <link rel="stylesheet" href="<?= url('assets/css/dashboard.css') ?>">
    <?php endif; ?>
</head>
<body data-base-url="<?= e(BASE_URL) ?>" class="<?= $isDashboard ? 'dashboard-body' : 'public-body' ?>">

<?php if (!$isDashboard): ?>
    <!-- Public Header Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-custom sticky-top">
        <div class="container">
            <a class="navbar-brand navbar-brand-logo" href="<?= url('index.php') ?>">
                <span class="brand-icon">
                    <i class="bi bi-heart-pulse-fill"></i>
                </span>
                <span>VetCare<span class="text-primary">.Solution</span></span>
            </a>
            
            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?= $activeNav === 'home' ? 'active' : '' ?>" href="<?= url('index.php') ?>">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeNav === 'about' ? 'active' : '' ?>" href="<?= url('about.php') ?>">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeNav === 'services' ? 'active' : '' ?>" href="<?= url('services.php') ?>">Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeNav === 'veterinarians' ? 'active' : '' ?>" href="<?= url('veterinarians.php') ?>">Veterinarians</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeNav === 'contact' ? 'active' : '' ?>" href="<?= url('contact.php') ?>">Contact</a>
                    </li>
                </ul>
                
                <div class="d-flex align-items-center gap-2">
                    <?php if (is_logged_in()): ?>
                        <?php 
                            $dashboardUrl = match(current_role()) {
                                ROLE_OWNER => url('owner/dashboard.php'),
                                ROLE_VET   => url('veterinarian/dashboard.php'),
                                ROLE_ADMIN => url('admin/dashboard.php'),
                                default    => url('index.php')
                            };
                        ?>
                        <a href="<?= $dashboardUrl ?>" class="btn btn-primary d-inline-flex align-items-center gap-2">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                        <form action="<?= url('auth/logout.php') ?>" method="POST" class="d-inline m-0">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-outline-secondary btn-sm" title="Sign Out">
                                <i class="bi bi-box-arrow-right"></i>
                            </button>
                        </form>
                    <?php else: ?>
                        <a href="<?= url('auth/login.php') ?>" class="btn btn-outline-primary px-3">Sign In</a>
                        <div class="dropdown">
                            <button class="btn btn-primary dropdown-toggle px-3" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Register
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">
                                <li>
                                    <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="<?= url('auth/register_owner.php') ?>">
                                        <i class="bi bi-person-heart text-primary fs-5"></i>
                                        <div>
                                            <strong>Animal Owner</strong>
                                            <div class="text-muted small">Register your animals & book visits</div>
                                        </div>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="<?= url('auth/register_vet.php') ?>">
                                        <i class="bi bi-hospital text-success fs-5"></i>
                                        <div>
                                            <strong>Veterinarian</strong>
                                            <div class="text-muted small">Join our clinical provider network</div>
                                        </div>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
<?php endif; ?>
