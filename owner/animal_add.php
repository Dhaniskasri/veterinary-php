<?php
/**
 * Register New Animal
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

require_role(ROLE_OWNER);

$pageTitle = 'Register New Animal';
$isDashboard = true;
$activePage = 'animal_add';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/owner_sidebar.php'; ?>

    <main class="dashboard-main">
        <header class="dashboard-topbar">
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="btn btn-light d-lg-none p-2 rounded-3 border" id="sidebarToggle" aria-label="Toggle Navigation">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <div>
                    <h5 class="fw-bold mb-0 text-dark">Register New Animal</h5>
                    <small class="text-muted">Companion pets, horses, and farm livestock</small>
                </div>
            </div>
            <div>
                <a href="<?= url('owner/animals.php') ?>" class="btn btn-outline-secondary rounded-pill px-3">
                    <i class="bi bi-arrow-left me-1"></i> Back to Animals
                </a>
            </div>
        </header>

        <div class="dashboard-content-body">
            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white max-w-900 mx-auto">
                <form action="<?= url('actions/animal_save.php') ?>" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <?= csrf_field() ?>

                    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">1. Identification & Species</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="animal_name" class="form-label">Animal Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="animal_name" name="animal_name" placeholder="e.g. Buddy, Bella, Thunder" required autofocus>
                            <div class="invalid-feedback">Please enter the animal's name.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="species" class="form-label">Species <span class="text-danger">*</span></label>
                            <select class="form-select" id="species" name="species" required>
                                <option value="">Select Animal Species...</option>
                                <?php foreach (ANIMAL_SPECIES as $key => $label): ?>
                                    <option value="<?= e($key) ?>"><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select a species.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="breed" class="form-label">Breed</label>
                            <input type="text" class="form-control" id="breed" name="breed" placeholder="e.g. Golden Retriever, Holstein Friesian, Persian">
                        </div>

                        <div class="col-md-6">
                            <label for="gender" class="form-label">Gender</label>
                            <select class="form-select" id="gender" name="gender">
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="unknown">Unknown</option>
                            </select>
                        </div>
                    </div>

                    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">2. Physical & Vitals Information</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label for="date_of_birth" class="form-label">Date of Birth (if known)</label>
                            <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" max="<?= date('Y-m-d') ?>">
                        </div>

                        <div class="col-md-4">
                            <label for="age_years" class="form-label">Approximate Age (Years)</label>
                            <input type="number" class="form-control" id="age_years" name="age_years" placeholder="e.g. 3" min="0" max="60">
                        </div>

                        <div class="col-md-4">
                            <label for="weight" class="form-label">Weight (kg)</label>
                            <input type="number" step="0.1" class="form-control" id="weight" name="weight" placeholder="e.g. 25.5" min="0">
                        </div>

                        <div class="col-md-6">
                            <label for="color" class="form-label">Color / Markings</label>
                            <input type="text" class="form-control" id="color" name="color" placeholder="e.g. Black and white spots, Brown coat">
                        </div>

                        <div class="col-md-6">
                            <label for="identification_number" class="form-label">Microchip ID / Ear Tag Number</label>
                            <input type="text" class="form-control" id="identification_number" name="identification_number" placeholder="e.g. CHIP-9821-US or TAG-7801">
                        </div>
                    </div>

                    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">3. Medical History & Photograph</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="allergies" class="form-label">Known Allergies / Sensitivities</label>
                            <textarea class="form-control" id="allergies" name="allergies" rows="3" placeholder="e.g. Allergic to penicillin, sensitive to dairy food..."></textarea>
                        </div>

                        <div class="col-md-6">
                            <label for="notes" class="form-label">Dietary or Behavioral Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="e.g. Friendly with other animals, prone to mild ear itching..."></textarea>
                        </div>

                        <div class="col-12">
                            <label for="photo" class="form-label">Animal Photograph (Optional)</label>
                            <input type="file" class="form-control" id="photo" name="photo" accept=".jpg,.jpeg,.png,.webp">
                            <div class="form-text">Accepted formats: JPG, PNG, WEBP (Max 5MB). Images will be stored securely.</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="<?= url('owner/animals.php') ?>" class="btn btn-light border px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-circle-fill me-1"></i> Save Animal Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
