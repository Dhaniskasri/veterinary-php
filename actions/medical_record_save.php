<?php
/**
 * Medical Record Save Action (with dynamic Prescriptions)
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

require_role([ROLE_VET, ROLE_ADMIN]);
validate_csrf_or_die();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('veterinarian/dashboard.php');
}

$user = current_user();
$userId = (int)$user['id'];
$userRole = $user['role'];

$recordId = (int)($_POST['record_id'] ?? 0);
$animalId = (int)($_POST['animal_id'] ?? 0);
$appointmentId = !empty($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : null;
$vetId = ($userRole === ROLE_ADMIN && !empty($_POST['veterinarian_id'])) ? (int)$_POST['veterinarian_id'] : $userId;

$visitDate = trim($_POST['visit_date'] ?? date('Y-m-d'));
$symptoms = trim($_POST['symptoms'] ?? '');
$diagnosis = trim($_POST['diagnosis'] ?? '');
$treatmentPlan = trim($_POST['treatment_plan'] ?? '');
$clinicalNotes = trim($_POST['clinical_notes'] ?? '');
$followUpDate = !empty($_POST['follow_up_date']) ? $_POST['follow_up_date'] : null;

$prescriptions = $_POST['prescriptions'] ?? [];

$errors = [];

if ($animalId <= 0) $errors[] = 'Please select a patient animal.';
if (empty($visitDate)) $errors[] = 'Visit date is required.';
if (empty($symptoms)) $errors[] = 'Symptoms are required.';
if (empty($diagnosis)) $errors[] = 'Diagnosis is required.';
if (empty($treatmentPlan)) $errors[] = 'Treatment plan is required.';

if (!empty($errors)) {
    $back = ($recordId > 0) ? 'veterinarian/medical_record_edit.php?id=' . $recordId : 'veterinarian/medical_record_add.php';
    redirect($back, implode(' ', $errors), 'danger');
}

try {
    $pdo->beginTransaction();

    if ($recordId > 0) {
        // Update existing record
        $stmt = $pdo->prepare("
            UPDATE medical_records SET
                visit_date = :vdate,
                symptoms = :symptoms,
                diagnosis = :diag,
                treatment_plan = :treatment,
                clinical_notes = :notes,
                follow_up_date = :followup,
                updated_at = NOW()
            WHERE id = :id AND veterinarian_id = :vet_id
        ");
        $stmt->execute([
            ':vdate'     => $visitDate,
            ':symptoms'  => $symptoms,
            ':diag'      => $diagnosis,
            ':treatment' => $treatmentPlan,
            ':notes'     => $clinicalNotes,
            ':followup'  => $followUpDate,
            ':id'        => $recordId,
            ':vet_id'    => ($userRole === ROLE_ADMIN) ? $recordId : $userId
        ]);

        $savedRecordId = $recordId;
        log_activity($pdo, $userId, 'MEDICAL_RECORD_UPDATED', 'medical_record', $savedRecordId);
    } else {
        // Insert new record
        $stmt = $pdo->prepare("
            INSERT INTO medical_records 
            (animal_id, appointment_id, veterinarian_id, visit_date, symptoms, diagnosis, treatment_plan, clinical_notes, follow_up_date, created_at)
            VALUES (:animal_id, :app_id, :vet_id, :vdate, :symptoms, :diag, :treatment, :notes, :followup, NOW())
        ");
        $stmt->execute([
            ':animal_id' => $animalId,
            ':app_id'    => $appointmentId,
            ':vet_id'    => $vetId,
            ':vdate'     => $visitDate,
            ':symptoms'  => $symptoms,
            ':diag'      => $diagnosis,
            ':treatment' => $treatmentPlan,
            ':notes'     => $clinicalNotes,
            ':followup'  => $followUpDate
        ]);

        $savedRecordId = (int)$pdo->lastInsertId();

        // If linked to an appointment, mark appointment as completed
        if ($appointmentId) {
            $upApp = $pdo->prepare("UPDATE appointments SET appointment_status = 'completed', updated_at = NOW() WHERE id = ?");
            $upApp->execute([$appointmentId]);
        }

        // Insert multiple prescription items
        if (!empty($prescriptions) && is_array($prescriptions)) {
            $rxStmt = $pdo->prepare("
                INSERT INTO prescriptions (medical_record_id, medicine_name, dosage, frequency, duration, instructions, created_at)
                VALUES (:mr_id, :name, :dosage, :freq, :dur, :inst, NOW())
            ");

            foreach ($prescriptions as $rx) {
                $medName = trim($rx['medicine_name'] ?? '');
                $dosage  = trim($rx['dosage'] ?? '');
                $freq    = trim($rx['frequency'] ?? '');
                $dur     = trim($rx['duration'] ?? '');
                $inst    = trim($rx['instructions'] ?? '');

                if (!empty($medName) && !empty($dosage)) {
                    $rxStmt->execute([
                        ':mr_id'  => $savedRecordId,
                        ':name'   => substr($medName, 0, 150),
                        ':dosage' => substr($dosage, 0, 100),
                        ':freq'   => substr($freq, 0, 100),
                        ':dur'    => substr($dur, 0, 100),
                        ':inst'   => $inst
                    ]);
                }
            }
        }

        log_activity($pdo, $userId, 'MEDICAL_RECORD_CREATED', 'medical_record', $savedRecordId);
    }

    $pdo->commit();

    redirect(
        'owner/prescription_view.php?record_id=' . $savedRecordId,
        'Consultation record and prescription successfully finalized!',
        'success'
    );
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Medical record save error: " . $e->getMessage());
    $back = ($recordId > 0) ? 'veterinarian/medical_record_edit.php?id=' . $recordId : 'veterinarian/medical_record_add.php';
    redirect($back, 'Database error while saving medical record: ' . $e->getMessage(), 'danger');
}
