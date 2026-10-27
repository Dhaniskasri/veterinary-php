<?php
/**
 * About Us Page
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'About Our Veterinary Care Mission';
$activeNav = 'about';

require_once __DIR__ . '/includes/header.php';
?>

<div class="bg-primary-subtle py-5 border-bottom">
    <div class="container text-center">
        <span class="badge bg-primary text-white rounded-pill px-3 py-2 mb-2">Our Healthcare Philosophy</span>
        <h1 class="fw-bold text-dark">About Veterinary Care for Animal Medical Solution</h1>
        <p class="text-muted lead max-w-700 mx-auto">
            A trusted digital animal health platform built to enhance clinical communication, maintain reliable electronic health records, and support pet wellness and livestock health.
        </p>
    </div>
</div>

<div class="container py-5">
    <div class="row g-5 align-items-center mb-5">
        <div class="col-lg-6">
            <h6 class="text-uppercase text-primary fw-bold letter-spacing-1">Our Core Mission</h6>
            <h2 class="fw-bold mb-3">Compassionate Healthcare Grounded in Modern Technology</h2>
            <p class="text-secondary">
                Animal health is essential to families, farmers, and communities. Traditional paper-based veterinary records and informal scheduling often lead to missed immunizations, duplicate bookings, and fragmented medical histories.
            </p>
            <p class="text-secondary">
                Our application solves this challenge by delivering a centralized, role-based platform where animal owners can track all their animals—from companion dogs and cats to large livestock herds—while authorized veterinarians maintain verified clinical observations, diagnoses, and digital prescriptions.
            </p>
            <div class="row g-3 pt-2">
                <div class="col-sm-6">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-shield-check text-success fs-4"></i>
                        <span class="fw-semibold text-dark">Strict Role-Based Security</span>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-calendar2-check text-primary fs-4"></i>
                        <span class="fw-semibold text-dark">Zero-Conflict Scheduling</span>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-file-medical text-info fs-4"></i>
                        <span class="fw-semibold text-dark">Electronic Medical Records</span>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-bell text-warning fs-4"></i>
                        <span class="fw-semibold text-dark">Proactive Vaccine Alerts</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-lg rounded-4 p-4 bg-white">
                <h4 class="fw-bold mb-3 text-dark"><i class="bi bi-layers text-primary me-2"></i> Technical Architecture</h4>
                <p class="text-muted small">
                    This application is engineered following clean full-stack architectural principles without heavy external framework bloat:
                </p>
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span><strong class="text-dark">Backend:</strong> Core PHP 8+ with Object-Oriented PDO</span>
                        <span class="badge bg-primary-subtle text-primary">Native PHP</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span><strong class="text-dark">Database:</strong> MySQL InnoDB with Foreign Keys & Transactions</span>
                        <span class="badge bg-success-subtle text-success">ACID Compliant</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span><strong class="text-dark">Security:</strong> Bcrypt Password Hashing & CSRF Protection</span>
                        <span class="badge bg-danger-subtle text-danger">OWASP Aligned</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span><strong class="text-dark">Frontend:</strong> HTML5, Vanilla CSS3, Bootstrap 5 & Chart.js</span>
                        <span class="badge bg-info-subtle text-info">Responsive</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span><strong class="text-dark">Deployment:</strong> Local XAMPP (Apache + MySQL + phpMyAdmin)</span>
                        <span class="badge bg-secondary-subtle text-secondary">Zero-Config</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Educational Purpose Notice -->
    <div class="medical-disclaimer-box mb-5">
        <div class="d-flex align-items-start gap-3">
            <i class="bi bi-exclamation-triangle-fill fs-3 text-warning"></i>
            <div>
                <h5 class="fw-bold mb-1">Academic & Educational Project Disclaimer</h5>
                <p class="mb-0">
                    This web application is developed strictly as a college-level academic project for demonstrating full-stack PHP and MySQL database design, role-based access control, appointment booking workflows, and clinical CRUD operations. It does not provide real veterinary medical advice, physical examinations, or emergency hospital triage. For actual animal healthcare emergencies, please contact a licensed local animal clinic immediately.
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
