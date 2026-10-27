<?php
/**
 * Public Home Page
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Compassionate Care for Every Animal';
$activeNav = 'home';

// Fetch approved veterinarians for highlight section
$vetsStmt = $pdo->query("
    SELECT u.id, u.full_name, vp.qualification, vp.specialization, vp.experience_years, vp.consultation_fee, vp.bio
    FROM users u
    JOIN veterinarian_profiles vp ON vp.user_id = u.id
    WHERE u.role = 'veterinarian' AND u.account_status = 'active' AND vp.approval_status = 'approved'
    ORDER BY vp.experience_years DESC
    LIMIT 3
");
$featuredVets = $vetsStmt->fetchAll();

// Dynamic counters from database
$totalAnimals = $pdo->query("SELECT COUNT(*) FROM animals")->fetchColumn();
$totalVets = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'veterinarian' AND account_status = 'active'")->fetchColumn();
$totalAppointments = $pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_status = 'completed'")->fetchColumn();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <span class="hero-badge">
                    <i class="bi bi-shield-check"></i> Certified Veterinary Platform
                </span>
                <h1 class="hero-title">
                    Compassionate Care for <span class="text-highlight">Every Animal</span>
                </h1>
                <p class="hero-lead">
                    A modern, unified digital platform connecting animal owners with certified veterinary surgeons and specialists. Seamlessly manage pet wellness, livestock herd health, appointment scheduling, and electronic medical histories.
                </p>
                <div class="d-flex flex-wrap gap-3 mb-4">
                    <a href="<?= url('owner/appointment_add.php') ?>" class="btn btn-primary btn-lg btn-pill px-4">
                        <i class="bi bi-calendar2-check me-2"></i> Book an Appointment
                    </a>
                    <a href="<?= url('auth/register_owner.php') ?>" class="btn btn-outline-primary btn-lg btn-pill px-4">
                        <i class="bi bi-person-plus me-2"></i> Get Started
                    </a>
                </div>
                <div class="d-flex align-items-center gap-4 text-muted small pt-2">
                    <div><i class="bi bi-check-circle-fill text-success me-1"></i> Board-Certified Vets</div>
                    <div><i class="bi bi-check-circle-fill text-success me-1"></i> Digital Prescriptions</div>
                    <div><i class="bi bi-check-circle-fill text-success me-1"></i> Vaccination Alerts</div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card shadow-lg border-0 rounded-4 overflow-hidden p-4 bg-white">
                    <div class="d-flex align-items-center justify-content-between pb-3 border-bottom mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-primary-subtle text-primary p-3 rounded-circle">
                                <i class="bi bi-shield-plus fs-3"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">Live System Metrics</h6>
                                <small class="text-muted">Real-time database statistics</small>
                            </div>
                        </div>
                        <span class="badge bg-success-subtle text-success">Active</span>
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3 text-center">
                                <div class="display-6 fw-bold text-primary mb-1"><?= (int)$totalAnimals ?></div>
                                <div class="text-muted small fw-medium">Registered Animals</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3 text-center">
                                <div class="display-6 fw-bold text-info mb-1"><?= (int)$totalVets ?></div>
                                <div class="text-muted small fw-medium">Active Specialists</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="p-3 bg-light rounded-3 d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-calendar2-check-fill text-success fs-4"></i>
                                    <span class="text-dark fw-semibold small">Consultations Completed</span>
                                </div>
                                <span class="badge bg-dark rounded-pill fs-6"><?= (int)$totalAppointments ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top text-center">
                        <p class="text-muted small mb-0">Emergency veterinary support available round the clock.</p>
                        <a href="tel:<?= e(EMERGENCY_PHONE) ?>" class="text-danger fw-bold small text-decoration-none">
                            <i class="bi bi-telephone-fill me-1"></i> Call Emergency: <?= e(EMERGENCY_PHONE) ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Supported Animal Species Section -->
<section class="py-5 bg-white border-bottom">
    <div class="container text-center">
        <h6 class="text-uppercase text-primary fw-bold letter-spacing-1 mb-2">Comprehensive Animal Coverage</h6>
        <h2 class="fw-bold mb-4">Dedicated Healthcare for Companion Pets & Farm Livestock</h2>
        <div class="row g-3 justify-content-center">
            <?php 
            $speciesCards = [
                ['name' => 'Dogs', 'sub' => 'Canine Care', 'icon' => 'bi-heart-fill', 'color' => '#0d9488'],
                ['name' => 'Cats', 'sub' => 'Feline Medicine', 'icon' => 'bi-stars', 'color' => '#0284c7'],
                ['name' => 'Cows & Buffaloes', 'sub' => 'Bovine & Dairy', 'icon' => 'bi-shield-check', 'color' => '#16a34a'],
                ['name' => 'Horses', 'sub' => 'Equine Sports', 'icon' => 'bi-trophy-fill', 'color' => '#d97706'],
                ['name' => 'Goats & Sheep', 'sub' => 'Small Ruminants', 'icon' => 'bi-suit-heart-fill', 'color' => '#9333ea'],
                ['name' => 'Poultry & Birds', 'sub' => 'Avian Medicine', 'icon' => 'bi-egg-fill', 'color' => '#e11d48']
            ];
            foreach ($speciesCards as $sc): ?>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="p-3 border rounded-3 text-center h-100 hover-shadow transition">
                        <div class="mb-2" style="font-size: 1.8rem; color: <?= $sc['color'] ?>;">
                            <i class="bi <?= $sc['icon'] ?>"></i>
                        </div>
                        <h6 class="fw-bold mb-0 text-dark"><?= $sc['name'] ?></h6>
                        <small class="text-muted" style="font-size: 0.75rem;"><?= $sc['sub'] ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Core Clinical Services -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center max-w-700 mx-auto mb-5">
            <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fw-semibold mb-2">Our Specialized Care</span>
            <h2 class="fw-bold">Everything Your Animals Need for a Healthy Life</h2>
            <p class="text-muted">From routine preventive wellness checks to advanced surgical diagnostics, our network provides complete clinical management.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon-wrapper feature-icon-teal">
                        <i class="bi bi-calendar2-week"></i>
                    </div>
                    <h5 class="fw-bold">Veterinary Consultations</h5>
                    <p class="text-muted small">
                        Book direct in-person and scheduled clinical visits with accredited veterinarians. Real-time slot availability prevents double-booking.
                    </p>
                    <a href="<?= url('services.php') ?>" class="text-primary fw-semibold small">Learn more <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon-wrapper feature-icon-blue">
                        <i class="bi bi-eyedropper"></i>
                    </div>
                    <h5 class="fw-bold">Vaccination Schedules</h5>
                    <p class="text-muted small">
                        Never miss an essential vaccine. Maintain complete immunization records for rabies, DHPP, FVRCP, and livestock viral immunizations with upcoming due date reminders.
                    </p>
                    <a href="<?= url('services.php') ?>" class="text-primary fw-semibold small">Learn more <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon-wrapper feature-icon-emerald">
                        <i class="bi bi-prescription2"></i>
                    </div>
                    <h5 class="fw-bold">Digital Medical Records</h5>
                    <p class="text-muted small">
                        Secure electronic health records detailing clinical observations, differential diagnoses, treatment plans, and printable prescription instructions.
                    </p>
                    <a href="<?= url('services.php') ?>" class="text-primary fw-semibold small">Learn more <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon-wrapper feature-icon-amber">
                        <i class="bi bi-bell-fill"></i>
                    </div>
                    <h5 class="fw-bold">Follow-up Monitoring</h5>
                    <p class="text-muted small">
                        Stay informed on scheduled follow-ups, post-operative assessments, wound dressing rechecks, and chronic therapy monitoring right on your dashboard.
                    </p>
                    <a href="<?= url('services.php') ?>" class="text-primary fw-semibold small">Learn more <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon-wrapper feature-icon-teal">
                        <i class="bi bi-shield-shaded"></i>
                    </div>
                    <h5 class="fw-bold">Livestock & Herd Health</h5>
                    <p class="text-muted small">
                        Specialized veterinary protocols for bovine, equine, caprine, and ovine populations including lactation tracking and herd deworming schedules.
                    </p>
                    <a href="<?= url('services.php') ?>" class="text-primary fw-semibold small">Learn more <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon-wrapper feature-icon-blue">
                        <i class="bi bi-activity"></i>
                    </div>
                    <h5 class="fw-bold">Emergency Referral Support</h5>
                    <p class="text-muted small">
                        Clear clinical guidance and immediate referral protocols for critical trauma, acute bloat, colic, poisoning, or difficult labor interventions.
                    </p>
                    <a href="<?= url('contact.php') ?>" class="text-primary fw-semibold small">Learn more <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="py-5 bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fw-semibold mb-2">Simple Workflow</span>
            <h2 class="fw-bold">How the Platform Works</h2>
            <p class="text-muted">A streamlined four-step process for animal owners and practitioners.</p>
        </div>

        <div class="row g-4 text-center">
            <div class="col-md-3">
                <div class="p-3">
                    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-circle" style="width: 70px; height: 70px; font-size: 1.8rem; font-weight: 700;">
                        1
                    </div>
                    <h5 class="fw-bold">Register Animal</h5>
                    <p class="text-muted small">Add your companion pet or livestock with species, breed, age, weight, and allergy history.</p>
                </div>
            </div>

            <div class="col-md-3">
                <div class="p-3">
                    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-circle" style="width: 70px; height: 70px; font-size: 1.8rem; font-weight: 700;">
                        2
                    </div>
                    <h5 class="fw-bold">Select Specialist</h5>
                    <p class="text-muted small">Choose an approved veterinarian matching your animal's species and clinical requirements.</p>
                </div>
            </div>

            <div class="col-md-3">
                <div class="p-3">
                    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-circle" style="width: 70px; height: 70px; font-size: 1.8rem; font-weight: 700;">
                        3
                    </div>
                    <h5 class="fw-bold">Choose Free Slot</h5>
                    <p class="text-muted small">Pick an available time slot. Our conflict detection guarantees no overlapping bookings.</p>
                </div>
            </div>

            <div class="col-md-3">
                <div class="p-3">
                    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-circle" style="width: 70px; height: 70px; font-size: 1.8rem; font-weight: 700;">
                        4
                    </div>
                    <h5 class="fw-bold">Access Records</h5>
                    <p class="text-muted small">Following consultation, view diagnosis, print medication prescriptions, and review due vaccines.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Approved Veterinarians Section -->
<section class="py-5 bg-light border-top">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4">
            <div>
                <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fw-semibold mb-2">Our Medical Board</span>
                <h2 class="fw-bold mb-1">Meet Certified Veterinarians</h2>
                <p class="text-muted mb-0">Experienced specialists dedicated to compassionate animal care.</p>
            </div>
            <div class="mt-3 mt-md-0">
                <a href="<?= url('veterinarians.php') ?>" class="btn btn-outline-primary">
                    View All Specialists <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        </div>

        <div class="row g-4">
            <?php foreach ($featuredVets as $vet): ?>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden p-4 bg-white">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold fs-4" style="width: 58px; height: 58px;">
                                <?= strtoupper(substr($vet['full_name'], 0, 1)) ?>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-1"><?= e($vet['full_name']) ?></h5>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    <i class="bi bi-patch-check-fill me-1"></i> Board Approved
                                </span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="text-primary fw-semibold small mb-1"><?= e($vet['specialization']) ?></div>
                            <div class="text-muted small"><?= e($vet['qualification']) ?></div>
                        </div>

                        <p class="text-secondary small flex-grow-1">
                            <?= e(substr($vet['bio'] ?? 'Dedicated veterinarian committed to clinical precision and patient recovery.', 0, 140)) ?>...
                        </p>

                        <div class="d-flex align-items-center justify-content-between pt-3 border-top mt-2">
                            <div>
                                <span class="text-muted small d-block">Consultation Fee</span>
                                <span class="fw-bold text-dark fs-5">$<?= number_format((float)$vet['consultation_fee'], 2) ?></span>
                            </div>
                            <a href="<?= url('owner/appointment_add.php?vet_id=' . $vet['id']) ?>" class="btn btn-sm btn-primary rounded-pill px-3">
                                Book Visit
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Call to Action Section -->
<section class="py-5 bg-primary text-white text-center">
    <div class="container py-4">
        <h2 class="display-6 fw-bold mb-3">Ready to give your animals the care they deserve?</h2>
        <p class="lead mb-4 text-white-50 max-w-700 mx-auto">
            Create an animal profile in seconds, book certified appointments, and keep clinical history at your fingertips.
        </p>
        <div class="d-flex flex-wrap justify-content-center gap-3">
            <a href="<?= url('auth/register_owner.php') ?>" class="btn btn-light btn-lg btn-pill px-4 text-primary fw-bold">
                Create Free Account
            </a>
            <a href="<?= url('contact.php') ?>" class="btn btn-outline-light btn-lg btn-pill px-4">
                Contact Our Team
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
