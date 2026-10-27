<?php
/**
 * Edit Animal Details
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

require_role(ROLE_OWNER);

$pageTitle = 'Edit Animal Details';
$isDashboard = true;
$activePage = 'animals';
$user = current_user();
$ownerId = (int)$user['id'];

$animalId = (int)($_GET['id'] ?? 0);
if ($animalId <= 0) {
    redirect('owner/animals.php', 'Invalid animal ID.', 'danger');
}

$stmt = $pdo->prepare("SELECT * FROM animals WHERE id = ? AND owner_id = ?");
$stmt->execute([$animalId, $ownerId]);
$animal = $stmt->fetch();

if (!$animal) {
    redirect('owner/animals.php', 'Animal not found or you do not have permission to edit this record.', 'danger');
}

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
                    <h5 class="fw-bold mb-0 text-dark">Edit: <?= e($animal['animal_name']) ?></h5>
                    <small class="text-muted">Update profile and physical vitals</small>
                </div>
            </div>
            <div>
                <a href="<?= url('owner/animal_view.php?id=' . $animalId) ?>" class="btn btn-outline-secondary rounded-pill px-3">
                    <i class="bi bi-arrow-left me-1"></i> Back to Profile
                </a>
            </div>
        </header>

        <div class="dashboard-content-body">
            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white max-w-900 mx-auto">
                <form action="<?= url('actions/animal_save.php') ?>" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="animal_id" value="<?= $animalId ?>">

                    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">1. Identification & Species</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="animal_name" class="form-label">Animal Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="animal_name" name="animal_name" value="<?= e($animal['animal_name']) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label for="species" class="form-label">Species <span class="text-danger">*</span></label>
                            <select class="form-select" id="species" name="species" required>
                                <?php foreach (ANIMAL_SPECIES as $key => $label): ?>
                                    <option value="<?= e($key) ?>" <?= $animal['species'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="breed" class="form-label">Breed</label>
                            <input type="text" class="form-control" id="breed" name="breed" value="<?= e($animal['breed']) ?>">
                        </div>

                        <div class="col-md-6">
                            <label for="gender" class="form-label">Gender</label>
                            <select class="form-select" id="gender" name="gender">
                                <option value="male" <?= $animal['gender'] === 'male' ? 'selected' : '' ?>>Male</option>
                                <option value="female" <?= $animal['gender'] === 'female' ? 'selected' : '' ?>>Female</option>
                                <option value="unknown" <?= $animal['gender'] === 'unknown' ? 'selected' : '' ?>>Unknown</option>
                            </select>
                        </div>
                    </div>

                    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">2. Physical & Vitals Information</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label for="date_of_birth" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" value="<?= e($animal['date_of_birth']) ?>" max="<?= date('Y-m-d') ?>">
                        </div>

                        <div class="col-md-4">
                            <label for="age_years" class="form-label">Age (Years)</label>
                            <input type="number" class="form-control" id="age_years" name="age_years" value="<?= e($animal['age_years']) ?>" min="0" max="60">
                        </div>

                        <div class="col-md-4">
                            <label for="weight" class="form-label">Weight (kg)</label>
                            <input type="number" step="0.1" class="form-control" id="weight" name="weight" value="<?= e($animal['weight']) ?>" min="0">
                        </div>

                        <div class="col-md-6">
                            <label for="color" class="form-label">Color / Markings</label>
                            <input type="text" class="form-control" id="color" name="color" value="<?= e($animal['color']) ?>">
                        </div>

                        <div class="col-md-6">
                            <label for="identification_number" class="form-label">Microchip ID / Ear Tag</label>
                            <input type="text" class="form-control" id="identification_number" name="identification_number" value="<?= e($animal['identification_number']) ?>">
                        </div>
                    </div>

                    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">3. Medical History & Photograph</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="allergies" class="form-label">Known Allergies / Sensitivities</label>
                            <textarea class="form-control" id="allergies" name="allergies" rows="3"><?= e($animal['allergies']) ?></textarea>
                        </div>

                        <div class="col-md-6">
                            <label for="notes" class="form-label">Dietary or Behavioral Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"><?= e($animal['notes']) ?></textarea>
                        </div>

                        <div class="col-12">
                            <label for="photo" class="form-label">Update Photograph</label>
                            <?php if (!empty($animal['photo_path']) && file_exists(UPLOAD_DIR . DIRECTORY_SEPARATOR . $animal['photo_path'])): ?>
                                <div class="d-flex align-items-center gap-3 mb-2">
                                    <img src="<?= e(UPLOAD_URL . '/' . $animal['photo_path']) ?>" alt="Current photo" class="rounded-3 border" style="width: 70px; height: 70px; object-fit: cover;">
                                    <span class="text-muted small">Current photo loaded. Upload a new file below if you wish to replace it.</span>
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" id="photo" name="photo" accept=".jpg,.jpeg,.png,.webp">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="<?= url('owner/animal_view.php?id=' . $animalId) ?>" class="btn btn-light border px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-circle-fill me-1"></i> Update Animal Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
