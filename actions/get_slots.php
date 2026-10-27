<?php
/**
 * AJAX Endpoint: Get Available Appointment Slots for Veterinarian on a Specific Date
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json; charset=utf-8');

$vetId = (int)($_GET['vet_id'] ?? 0);
$dateStr = trim($_GET['date'] ?? '');

if ($vetId <= 0 || empty($dateStr)) {
    echo json_encode(['success' => false, 'message' => 'Missing veterinarian or appointment date.']);
    exit;
}

// Validate date format and ensure it's not in the past
$dateTime = DateTime::createFromFormat('Y-m-d', $dateStr);
if (!$dateTime || $dateTime->format('Y-m-d') !== $dateStr) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format. Use YYYY-MM-DD.']);
    exit;
}

$today = new DateTime('today');
if ($dateTime < $today) {
    echo json_encode(['success' => false, 'message' => 'Appointments cannot be booked for past dates.']);
    exit;
}

// Check veterinarian validity
$vetStmt = $pdo->prepare("
    SELECT u.id, u.full_name, vp.approval_status 
    FROM users u 
    JOIN veterinarian_profiles vp ON vp.user_id = u.id 
    WHERE u.id = ? AND u.role = 'veterinarian' AND u.account_status = 'active' AND vp.approval_status = 'approved'
");
$vetStmt->execute([$vetId]);
$vet = $vetStmt->fetch();

if (!$vet) {
    echo json_encode(['success' => false, 'message' => 'Selected veterinarian is not active or approved.']);
    exit;
}

// Check veterinarian availability schedule for this date
$availStmt = $pdo->prepare("
    SELECT start_time, end_time, slot_duration_minutes, is_available
    FROM veterinarian_availability
    WHERE veterinarian_id = ? AND available_date = ?
    LIMIT 1
");
$availStmt->execute([$vetId, $dateStr]);
$avail = $availStmt->fetch();

$startTimeStr = '09:00:00';
$endTimeStr   = '17:00:00';
$durationMin  = 30;

if ($avail) {
    if (!$avail['is_available']) {
        echo json_encode(['success' => true, 'slots' => [], 'message' => 'The veterinarian is unavailable on this date.']);
        exit;
    }
    $startTimeStr = $avail['start_time'];
    $endTimeStr   = $avail['end_time'];
    $durationMin  = (int)$avail['slot_duration_minutes'] ?: 30;
}

// Fetch currently booked active slots for this veterinarian on this date
$bookedStmt = $pdo->prepare("
    SELECT appointment_time 
    FROM appointments 
    WHERE veterinarian_id = ? AND appointment_date = ? AND appointment_status IN ('pending', 'scheduled')
");
$bookedStmt->execute([$vetId, $dateStr]);
$bookedTimes = $bookedStmt->fetchAll(PDO::FETCH_COLUMN);

// Normalize booked times to HH:MM:SS
$bookedMap = [];
foreach ($bookedTimes as $bt) {
    $bookedMap[substr($bt, 0, 5)] = true;
}

// Generate slot intervals
$start = new DateTime($dateStr . ' ' . $startTimeStr);
$end   = new DateTime($dateStr . ' ' . $endTimeStr);

$slots = [];
$current = clone $start;

while ($current < $end) {
    $slotTime = $current->format('H:i:s');
    $slotTimeShort = $current->format('H:i');
    $formattedTime = $current->format('g:i A');

    // If date is today, check if time has already passed
    $isPast = false;
    if ($dateStr === date('Y-m-d')) {
        $now = new DateTime();
        if ($current <= $now) {
            $isPast = true;
        }
    }

    $isBooked = isset($bookedMap[$slotTimeShort]) || $isPast;

    $slots[] = [
        'time'           => $slotTime,
        'formatted_time' => $formattedTime,
        'is_booked'      => $isBooked,
        'is_past'        => $isPast
    ];

    $current->modify("+{$durationMin} minutes");
}

echo json_encode([
    'success'         => true,
    'vet_name'        => $vet['full_name'],
    'date'            => $dateStr,
    'slot_duration'   => $durationMin,
    'total_slots'     => count($slots),
    'slots'           => $slots
]);
