<?php
/**
 * Veterinary Services Page
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Veterinary Clinical Services';
$activeNav = 'services';

require_once __DIR__ . '/includes/header.php';
?>

<div class="bg-primary-subtle py-5 border-bottom">
    <div class="container text-center">
        <span class="badge bg-primary text-white rounded-pill px-3 py-2 mb-2">Comprehensive Animal Medicine</span>
        <h1 class="fw-bold text-dark">Our Veterinary Care Services</h1>
        <p class="text-muted lead max-w-700 mx-auto">
            From routine wellness examinations to farm livestock herd health and surgical consultations, discover our complete clinical offerings.
        </p>
    </div>
</div>

<div class="container py-5">
    <div class="row g-4 mb-5">
        <div class="col-lg-4 col-md-6">
            <div class="feature-card">
                <div class="feature-icon-wrapper feature-icon-teal">
                    <i class="bi bi-heart-pulse"></i>
                </div>
                <h4 class="fw-bold">General Wellness & Triage</h4>
                <p class="text-secondary small">
                    Physical examinations, heart and lung auscultation, dental evaluations, dermatological skin checks, and nutritional counseling for dogs, cats, and small animals.
                </p>
                <ul class="text-muted small ps-3 mb-4">
                    <li>Comprehensive physical assessment</li>
                    <li>Vital sign tracking (temp, HR, respiration)</li>
                    <li>Weight management and diet guidance</li>
                </ul>
                <div class="border-top pt-3 d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-primary">From $35.00</span>
                    <a href="<?= url('owner/appointment_add.php') ?>" class="btn btn-sm btn-outline-primary">Book Visit</a>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6">
            <div class="feature-card">
                <div class="feature-icon-wrapper feature-icon-blue">
                    <i class="bi bi-eyedropper"></i>
                </div>
                <h4 class="fw-bold">Vaccination & Immunization</h4>
                <p class="text-secondary small">
                    Core and non-core vaccinations administered by certified veterinarians with full batch documentation and automated dashboard reminders for upcoming boosters.
                </p>
                <ul class="text-muted small ps-3 mb-4">
                    <li>Rabies 1-year and 3-year boosters</li>
                    <li>Canine DHPP / Feline FVRCP core vaccines</li>
                    <li>Bovine FMD & Equine Tetanus toxoid</li>
                </ul>
                <div class="border-top pt-3 d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-primary">From $25.00</span>
                    <a href="<?= url('owner/appointment_add.php') ?>" class="btn btn-sm btn-outline-primary">Book Visit</a>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6">
            <div class="feature-card">
                <div class="feature-icon-wrapper feature-icon-emerald">
                    <i class="bi bi-shield-check"></i>
                </div>
                <h4 class="fw-bold">Livestock & Bovine Health</h4>
                <p class="text-secondary small">
                    On-farm health consultations for cattle, dairy buffaloes, sheep, and goats. Focus on herd reproductive health, mastitis management, and parasite control.
                </p>
                <ul class="text-muted small ps-3 mb-4">
                    <li>Lactation and udder health screening</li>
                    <li>Pregnancy diagnosis & breeding protocols</li>
                    <li>Herd deworming and mineral balances</li>
                </ul>
                <div class="border-top pt-3 d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-primary">From $60.00</span>
                    <a href="<?= url('owner/appointment_add.php') ?>" class="btn btn-sm btn-outline-primary">Book Visit</a>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6">
            <div class="feature-card">
                <div class="feature-icon-wrapper feature-icon-amber">
                    <i class="bi bi-scissors"></i>
                </div>
                <h4 class="fw-bold">Surgical & Dental Care</h4>
                <p class="text-secondary small">
                    Soft tissue surgery, spay/neuter sterilizations, mass removals, wound debridement, and ultrasonic dental scaling conducted with rigorous sterile protocols.
                </p>
                <ul class="text-muted small ps-3 mb-4">
                    <li>Pre-anesthetic blood screening</li>
                    <li>Continuous multi-parameter monitoring</li>
                    <li>Comprehensive post-op analgesia</li>
                </ul>
                <div class="border-top pt-3 d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-primary">Consultation First</span>
                    <a href="<?= url('owner/appointment_add.php') ?>" class="btn btn-sm btn-outline-primary">Book Visit</a>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6">
            <div class="feature-card">
                <div class="feature-icon-wrapper feature-icon-teal">
                    <i class="bi bi-file-earmark-medical"></i>
                </div>
                <h4 class="fw-bold">Digital Prescriptions</h4>
                <p class="text-secondary small">
                    Legible, electronic prescriptions specifying medication name, precise dosage, frequency, and duration. Easily accessible and printable by the animal owner anytime.
                </p>
                <ul class="text-muted small ps-3 mb-4">
                    <li>Antibiotic & analgesic regimen tracking</li>
                    <li>Clear administration instructions</li>
                    <li>Direct clinical follow-up integration</li>
                </ul>
                <div class="border-top pt-3 d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-primary">Included in Visit</span>
                    <a href="<?= url('owner/appointment_add.php') ?>" class="btn btn-sm btn-outline-primary">Book Visit</a>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6">
            <div class="feature-card">
                <div class="feature-icon-wrapper feature-icon-blue">
                    <i class="bi bi-bell-fill"></i>
                </div>
                <h4 class="fw-bold">Follow-Up & Chronic Management</h4>
                <p class="text-secondary small">
                    Scheduled checkups for chronic animal diseases like osteoarthritis, endocrine disorders (diabetes/Cushing's), renal support, and wound dressing changes.
                </p>
                <ul class="text-muted small ps-3 mb-4">
                    <li>Long-term therapeutic monitoring</li>
                    <li>Symptom progression logs</li>
                    <li>Timely doctor follow-up reminders</li>
                </ul>
                <div class="border-top pt-3 d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-primary">From $30.00</span>
                    <a href="<?= url('owner/appointment_add.php') ?>" class="btn btn-sm btn-outline-primary">Book Visit</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
