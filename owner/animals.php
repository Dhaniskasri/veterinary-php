<?php
/**
 * My Registered Animals
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

require_role(ROLE_OWNER);

$pageTitle = 'My Registered Animals';
$isDashboard = true;
$activePage = 'animals';
$user = current_user();
$ownerId = (int)$user['id'];

$search = trim($_GET['search'] ?? '');
$speciesFilter = trim($_GET['species'] ?? '');

$sql = "
    SELECT a.*,
           (SELECT COUNT(*) FROM appointments app WHERE app.animal_id = a.id) AS appointment_count,
           (SELECT COUNT(*) FROM medical_records mr WHERE mr.animal_id = a.id) AS medical_count,
           (SELECT COUNT(*) FROM vaccinations v WHERE v.animal_id = a.id) AS vaccination_count
    FROM animals a
    WHERE a.owner_id = :owner_id
";
$params = [':owner_id' => $ownerId];

if (!empty($search)) {
    $sql .= " AND (a.animal_name LIKE :search OR a.breed LIKE :search OR a.identification_number LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($speciesFilter)) {
    $sql .= " AND a.species = :species";
    $params[':species'] = $speciesFilter;
}

$sql .= " ORDER BY a.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$animals = $stmt->fetchAll();

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
                    <h5 class="fw-bold mb-0 text-dark">My Registered Animals</h5>
                    <small class="text-muted">Manage patient profiles, microchips, and records</small>
                </div>
            </div>
            <div>
                <a href="<?= url('owner/animal_add.php') ?>" class="btn btn-primary rounded-pill px-3">
                    <i class="bi bi-plus-circle me-1"></i> Add New Animal
                </a>
            </div>
        </header>

        <div class="dashboard-content-body">
            <?php require_once __DIR__ . '/../includes/alerts.php'; ?>

            <!-- Search and Filter Bar -->
            <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
                <form method="GET" action="<?= url('owner/animals.php') ?>" class="row g-2 align-items-center">
                    <div class="col-md-6 col-lg-5">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" class="form-control border-start-0" name="search" value="<?= e($search) ?>" placeholder="Search by animal name, breed, ID tag...">
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-4">
                        <select class="form-select" name="species" onchange="this.form.submit()">
                            <option value="">All Animal Species</option>
                            <?php foreach (ANIMAL_SPECIES as $key => $label): ?>
                                <option value="<?= e($key) ?>" <?= $speciesFilter === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 col-lg-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">Filter</button>
                        <?php if (!empty($search) || !empty($speciesFilter)): ?>
                            <a href="<?= url('owner/animals.php') ?>" class="btn btn-outline-secondary" title="Reset filters">
                                <i class="bi bi-arrow-clockwise"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Animals Grid -->
            <?php if (empty($animals)): ?>
                <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
                    <div class="empty-state-icon mx-auto mb-3">
                        <i class="bi bi-shield-x fs-2"></i>
                    </div>
                    <h4 class="fw-bold">No Animals Found</h4>
                    <p class="text-muted small">You have not registered any animals matching this filter.</p>
                    <a href="<?= url('owner/animal_add.php') ?>" class="btn btn-primary mx-auto rounded-pill px-4">
                        <i class="bi bi-plus-circle me-1"></i> Register Your First Animal
                    </a>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($animals as $animal): ?>
                        <div class="col-md-6 col-xl-4">
                            <div class="animal-profile-card">
                                <div class="animal-thumb-wrapper">
                                    <?php if (!empty($animal['photo_path']) && file_exists(UPLOAD_DIR . DIRECTORY_SEPARATOR . $animal['photo_path'])): ?>
                                        <img src="<?= e(UPLOAD_URL . '/' . $animal['photo_path']) ?>" alt="<?= e($animal['animal_name']) ?>">
                                    <?php else: ?>
                                        <div class="text-center text-muted p-4">
                                            <i class="bi <?= get_species_icon($animal['species']) ?> display-3 text-secondary opacity-50"></i>
                                            <div class="small mt-2 fw-medium"><?= e($animal['species']) ?></div>
                                        </div>
                                    <?php endif; ?>
                                    <span class="position-absolute top-0 end-0 m-3 badge bg-dark bg-opacity-75 rounded-pill px-2.5 py-1.5">
                                        <?= e($animal['species']) ?>
                                    </span>
                                </div>

                                <div class="p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="fw-bold text-dark mb-0"><?= e($animal['animal_name']) ?></h5>
                                        <span class="badge bg-light text-dark border">
                                            <?= ucfirst($animal['gender']) ?>
                                        </span>
                                    </div>

                                    <div class="text-muted small mb-3">
                                        <strong>Breed:</strong> <?= e($animal['breed'] ?: 'Mixed / Unknown') ?><br>
                                        <strong>Age:</strong> <?= $animal['age_years'] !== null ? $animal['age_years'] . ' years' : 'Not specified' ?> &bull;
                                        <strong>Weight:</strong> <?= $animal['weight'] !== null ? $animal['weight'] . ' kg' : '—' ?>
                                    </div>

                                    <?php if (!empty($animal['identification_number'])): ?>
                                        <div class="p-2 bg-light rounded-2 text-dark small mb-3 font-monospace">
                                            <i class="bi bi-upc-scan text-primary me-1"></i> ID: <?= e($animal['identification_number']) ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="d-flex justify-content-between text-muted small py-2 border-top border-bottom mb-3">
                                        <span><i class="bi bi-calendar2 me-1"></i> <?= $animal['appointment_count'] ?> Visits</span>
                                        <span><i class="bi bi-file-medical me-1"></i> <?= $animal['medical_count'] ?> Records</span>
                                        <span><i class="bi bi-eyedropper me-1"></i> <?= $animal['vaccination_count'] ?> Vaccines</span>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <a href="<?= url('owner/animal_view.php?id=' . $animal['id']) ?>" class="btn btn-sm btn-outline-primary flex-grow-1">
                                            <i class="bi bi-eye me-1"></i> View Profile
                                        </a>
                                        <a href="<?= url('owner/appointment_add.php?animal_id=' . $animal['id']) ?>" class="btn btn-sm btn-primary" title="Book visit">
                                            <i class="bi bi-calendar-plus"></i>
                                        </a>
                                        <a href="<?= url('owner/animal_edit.php?id=' . $animal['id']) ?>" class="btn btn-sm btn-light border" title="Edit details">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
