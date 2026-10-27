<?php
/**
 * Animal Save / Update Action
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

require_login();
validate_csrf_or_die();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('owner/animals.php');
}

$user = current_user();
$userId = (int)$user['id'];
$userRole = $user['role'];

$animalId = (int)($_POST['animal_id'] ?? 0);
$ownerId = ($userRole === ROLE_ADMIN && !empty($_POST['owner_id'])) ? (int)$_POST['owner_id'] : $userId;

$animalName = trim($_POST['animal_name'] ?? '');
$species = trim($_POST['species'] ?? '');
$breed = trim($_POST['breed'] ?? '');
$gender = trim($_POST['gender'] ?? 'unknown');
$dateOfBirth = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
$ageYears = isset($_POST['age_years']) && $_POST['age_years'] !== '' ? (int)$_POST['age_years'] : null;
$weight = isset($_POST['weight']) && $_POST['weight'] !== '' ? (float)$_POST['weight'] : null;
$color = trim($_POST['color'] ?? '');
$identNumber = trim($_POST['identification_number'] ?? '');
$allergies = trim($_POST['allergies'] ?? '');
$notes = trim($_POST['notes'] ?? '');

$errors = [];

if (empty($animalName)) $errors[] = 'Animal name is required.';
if (empty($species)) $errors[] = 'Please select a valid species.';
if (!in_array($gender, ['male', 'female', 'unknown'], true)) $gender = 'unknown';

// If editing, verify ownership
$existingPhoto = null;
if ($animalId > 0) {
    $checkStmt = $pdo->prepare("SELECT id, owner_id, photo_path FROM animals WHERE id = ?");
    $checkStmt->execute([$animalId]);
    $existingAnimal = $checkStmt->fetch();

    if (!$existingAnimal) {
        $errors[] = 'Animal record not found.';
    } elseif ($userRole !== ROLE_ADMIN && (int)$existingAnimal['owner_id'] !== $userId) {
        $errors[] = 'Unauthorized: You can only edit animals belonging to your account.';
    } else {
        $existingPhoto = $existingAnimal['photo_path'];
        $ownerId = (int)$existingAnimal['owner_id'];
    }
}

// Handle Photo Upload
$photoPath = $existingPhoto;
if (!empty($_FILES['photo']['name'])) {
    $uploadResult = handle_file_upload($_FILES['photo'], UPLOAD_DIR);
    if (!$uploadResult['success']) {
        $errors[] = $uploadResult['error'];
    } else {
        $photoPath = $uploadResult['path'];
    }
}

if (!empty($errors)) {
    $backUrl = ($animalId > 0) ? 'owner/animal_edit.php?id=' . $animalId : 'owner/animal_add.php';
    redirect($backUrl, implode(' ', $errors), 'danger');
}

try {
    if ($animalId > 0) {
        // Update
        $stmt = $pdo->prepare("
            UPDATE animals SET
                animal_name = :name,
                species = :species,
                breed = :breed,
                gender = :gender,
                date_of_birth = :dob,
                age_years = :age,
                weight = :weight,
                color = :color,
                identification_number = :ident,
                photo_path = :photo,
                allergies = :allergies,
                notes = :notes,
                updated_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute([
            ':name'      => substr($animalName, 0, 100),
            ':species'   => substr($species, 0, 50),
            ':breed'     => substr($breed, 0, 100),
            ':gender'    => $gender,
            ':dob'       => $dateOfBirth,
            ':age'       => $ageYears,
            ':weight'    => $weight,
            ':color'     => substr($color, 0, 50),
            ':ident'     => substr($identNumber, 0, 100),
            ':photo'     => $photoPath,
            ':allergies' => $allergies,
            ':notes'     => $notes,
            ':id'        => $animalId
        ]);

        log_activity($pdo, $userId, 'ANIMAL_UPDATED', 'animal', $animalId);
        $dest = ($userRole === ROLE_ADMIN) ? 'admin/animals.php' : 'owner/animal_view.php?id=' . $animalId;
        redirect($dest, 'Animal profile for "' . e($animalName) . '" updated successfully.', 'success');
    } else {
        // Insert
        $stmt = $pdo->prepare("
            INSERT INTO animals 
            (owner_id, animal_name, species, breed, gender, date_of_birth, age_years, weight, color, identification_number, photo_path, allergies, notes, created_at)
            VALUES (:owner_id, :name, :species, :breed, :gender, :dob, :age, :weight, :color, :ident, :photo, :allergies, :notes, NOW())
        ");
        $stmt->execute([
            ':owner_id'  => $ownerId,
            ':name'      => substr($animalName, 0, 100),
            ':species'   => substr($species, 0, 50),
            ':breed'     => substr($breed, 0, 100),
            ':gender'    => $gender,
            ':dob'       => $dateOfBirth,
            ':age'       => $ageYears,
            ':weight'    => $weight,
            ':color'     => substr($color, 0, 50),
            ':ident'     => substr($identNumber, 0, 100),
            ':photo'     => $photoPath,
            ':allergies' => $allergies,
            ':notes'     => $notes
        ]);

        $newAnimalId = (int)$pdo->lastInsertId();
        log_activity($pdo, $userId, 'ANIMAL_REGISTERED', 'animal', $newAnimalId);
        $dest = ($userRole === ROLE_ADMIN) ? 'admin/animals.php' : 'owner/animal_view.php?id=' . $newAnimalId;
        redirect($dest, 'Animal "' . e($animalName) . '" has been successfully registered.', 'success');
    }
} catch (PDOException $e) {
    error_log("Animal save error: " . $e->getMessage());
    $backUrl = ($animalId > 0) ? 'owner/animal_edit.php?id=' . $animalId : 'owner/animal_add.php';
    redirect($backUrl, 'Database error while saving animal details. Please try again.', 'danger');
}
