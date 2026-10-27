<?php
/**
 * Record Vaccination Administered
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

require_role([ROLE_VET, ROLE_ADMIN]);

$pageTitle = 'Record Vaccination';
$isDashboard = true;
$activePage = 'vaccination_add';
$user = current_user();

$preAnimalId = (int)($_GET['animal_id'] ?? 0);

// Fetch all animals
$animals = $pdo->query("
    SELECT a.id, a.animal_name, a.species, a.breed, u.full_name AS owner_name
    FROM animals a
    JOIN users u ON u.id = a.owner_id
    ORDER BY a.animal_name ASC
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/vet_sidebar.php'; ?>

    <main class="dashboard-main">
        <header class="dashboard-topbar">
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="btn btn-light d-lg-none p-2 rounded-3 border" id="sidebarToggle" aria-label="Toggle Navigation">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <div>
                    <h5 class="fw-bold mb-0 text-dark">Record Vaccination</h5>
                    <small class="text-muted">Document immunizations, lot numbers, and schedule next booster</small>
                </div>
            </div>
            <div>
                <a href="<?= url('veterinarian/dashboard.php') ?>" class="btn btn-outline-secondary rounded-pill px-3">
                    <i class="bi bi-arrow-left me-1"></i> Dashboard
                </a>
            </div>
        </header>

        <div class="dashboard-content-body">
            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white max-w-750 mx-auto">
                <form action="<?= url('actions/vaccination_save.php') ?>" method="POST" class="needs-validation" novalidate>
                    <?= csrf_field() ?>

                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label for="animal_id" class="form-label">Patient Animal <span class="text-danger">*</span></label>
                            <select class="form-select" id="animal_id" name="animal_id" required>
                                <option value="">Select Patient...</option>
                                <?php foreach ($animals as $a): ?>
                                    <option value="<?= $a['id'] ?>" <?= $preAnimalId === (int)$a['id'] ? 'selected' : '' ?>>
                                        <?= e($a['animal_name']) ?> (<?= e($a['species']) ?> - <?= e($a['breed'] ?: 'Mixed') ?> &bull; Owner: <?= e($a['owner_name']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select an animal.</div>
                        </div>

                        <div class="col-md-12">
                            <label for="vaccine_name" class="form-label">Vaccine / Biologic Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="vaccine_name" name="vaccine_name" list="vaccineSuggestions" placeholder="e.g. Rabies (3-Year Booster), DHPP Canine Core, FVRCP Feline Core, Bovine FMD" required>
                            <datalist id="vaccineSuggestions">
                                <option value="Rabies (1-Year Booster)">
                                <option value="Rabies (3-Year Booster)">
                                <option value="Canine DHPP (Distemper, Hepatitis, Parvovirus, Parainfluenza)">
                                <option value="Canine Bordetella (Kennel Cough)">
                                <option value="Canine Leptospirosis 4-Way">
                                <option value="Feline FVRCP Core Vaccine">
                                <option value="Feline Leukemia Virus (FeLV)">
                                <option value="Bovine Foot & Mouth Disease (FMD)">
                                <option value="Bovine Hemorrhagic Septicemia (HS)">
                                <option value="Bovine Blackquarter (BQ)">
                                <option value="Equine Tetanus Toxoid">
                                <option value="Equine Influenza & Rhinopneumonitis">
                                <option value="Caprine / Ovine Enterotoxemia & Tetanus (CD&T)">
                            </datalist>
                            <div class="invalid-feedback">Please enter vaccine name.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="vaccination_date" class="form-label">Date Administered <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="vaccination_date" name="vaccination_date" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label for="next_due_date" class="form-label">Next Booster Due Date</label>
                            <input type="date" class="form-control" id="next_due_date" name="next_due_date" min="<?= date('Y-m-d') ?>">
                            <small class="text-muted">Calculates proactive reminders on owner dashboard.</small>
                        </div>

                        <div class="col-md-6">
                            <label for="batch_number" class="form-label">Lot / Batch Number</label>
                            <input type="text" class="form-control" id="batch_number" name="batch_number" placeholder="e.g. RB-2026-8812-A">
                        </div>

                        <div class="col-md-6">
                            <label for="injection_site" class="form-label">Administration Site / Route</label>
                            <input type="text" class="form-control" id="injection_site" placeholder="e.g. Subcutaneous right shoulder">
                        </div>

                        <div class="col-12">
                            <label for="notes" class="form-label">Clinical Observations & Adverse Reaction Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Note patient tolerance, manufacturer, serial number, or pre-vaccination vitals..."></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="<?= url('veterinarian/dashboard.php') ?>" class="btn btn-light border px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-eyedropper me-1"></i> Save Vaccination Record
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
