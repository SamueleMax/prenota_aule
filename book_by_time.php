<?php
require 'config.php';
requireLogin();
include 'header.php';

$date = $_GET['date'] ?? date('Y-m-d');
$start_hour = isset($_GET['start_hour']) ? (int)$_GET['start_hour'] : 1;
$end_hour = isset($_GET['end_hour']) ? (int)$_GET['end_hour'] : 1;
$success_msg = '';
$error_msg = '';

// Handle Booking Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['room_id'])) {
    $room_id = $_POST['room_id'];
    $post_date = $_POST['date'];
    $post_start = $_POST['start_hour'];
    $post_end = $_POST['end_hour'];

    // Double check availability
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM bookings 
        WHERE room_id = ? 
        AND booking_date = ? 
        AND (
            (start_hour <= ? AND end_hour >= ?) OR
            (start_hour <= ? AND end_hour >= ?) OR
            (start_hour >= ? AND end_hour <= ?)
        )
    ");
    // Overlap logic: (ReqStart <= ExistingEnd) and (ReqEnd >= ExistingStart)
    // Simplified checking logic above: if any existing booking overlaps. 
    // Actually, simple overlap logic: NOT (End <= ExistingStart OR Start >= ExistingEnd)
    // But since we store ranges inclusive for display, effectively users book 1st hour aka 8-9.
    // If I book hour 1-1, I occupy hour 1. 
    // If I book 1-2, I occupy hours 1 and 2.
    // Query: Any booking where NOT (existing_end < req_start OR existing_start > req_end)
    // Wait, hours are integers here. 
    // If request 1-2. Existing 3-4. No conflict.
    // If request 1-2. Existing 2-3. Conflict on 2? 
    // Let's standardise: Hour X means the slot starting at X. 
    // Start=1, End=1 means booking the 8-9 slot.
    // Start=1, End=2 means booking 8-10.
    
    // Actually, conflict query:
    // User wants S to E. 
    // Existing is ES to EE.
    // Conflict if: MAX(S, ES) <= MIN(E, EE) 
    
    $checkQuery = "
        SELECT COUNT(*) FROM bookings 
        WHERE room_id = ? 
        AND booking_date = ? 
        AND NOT (end_hour < ? OR start_hour > ?)
    ";
    
    $stmt = $pdo->prepare($checkQuery);
    $stmt->execute([$room_id, $post_date, $post_start, $post_end]);
    
    if ($stmt->fetchColumn() > 0) {
        $error_msg = "L'aula selezionata non è più disponibile per questo orario.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO bookings (user_id, room_id, booking_date, start_hour, end_hour) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $room_id, $post_date, $post_start, $post_end]);
        $success_msg = "Prenotazione confermata!";
    }
}

// Search Logic
$available_rooms = [];
if (isset($_GET['search'])) {
    if ($start_hour > $end_hour) {
        $error_msg = "L'ora di fine deve essere uguale o successiva all'ora di inizio.";
    } else {
        // Find rooms that do NOT have a conflict
        $sql = "
            SELECT * FROM rooms 
            WHERE is_enabled = 1 
            AND id NOT IN (
                SELECT room_id FROM bookings 
                WHERE booking_date = ? 
                AND NOT (end_hour < ? OR start_hour > ?)
            )
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$date, $start_hour, $end_hour]);
        $available_rooms = $stmt->fetchAll();
    }
}
?>

<div class="row">
    <div class="col-md-12">
        <h2>Prenota per Orario</h2>
        <p class="text-muted">Seleziona data e fascia oraria per vedere le aule disponibili.</p>
    </div>
</div>

<?php if($success_msg): ?>
    <div class="alert alert-success"><?php echo $success_msg; ?></div>
<?php endif; ?>
<?php if($error_msg): ?>
    <div class="alert alert-danger"><?php echo $error_msg; ?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Data</label>
                <input type="date" name="date" class="form-control" value="<?php echo $date; ?>" min="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Dall'ora</label>
                <select name="start_hour" class="form-select">
                    <?php foreach($hours_mapping as $h => $label): ?>
                        <option value="<?php echo $h; ?>" <?php echo $h == $start_hour ? 'selected' : ''; ?>>
                            <?php echo $h; ?>° Ora (<?php echo explode(' - ', $label)[0]; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Fino all'ora</label>
                <select name="end_hour" class="form-select">
                    <?php foreach($hours_mapping as $h => $label): ?>
                        <option value="<?php echo $h; ?>" <?php echo $h == $end_hour ? 'selected' : ''; ?>>
                            <?php echo $h; ?>° Ora (finisce <?php echo explode(' - ', $label)[1]; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" name="search" value="1" class="btn btn-primary w-100">Cerca</button>
            </div>
        </form>
    </div>
</div>

<?php if(isset($_GET['search']) && empty($error_msg)): ?>
    <h4>Aule Disponibili</h4>
    <?php if(count($available_rooms) > 0): ?>
        <div class="row">
            <?php foreach($available_rooms as $room): ?>
                <div class="col-md-3 mb-3">
                    <div class="card text-center h-100">
                        <div class="card-body d-flex flex-column justify-content-between">
                            <h3 class="card-title text-primary"><?php echo htmlspecialchars($room['name']); ?></h3>
                            <form method="POST" class="mt-3">
                                <input type="hidden" name="date" value="<?php echo $date; ?>">
                                <input type="hidden" name="start_hour" value="<?php echo $start_hour; ?>">
                                <input type="hidden" name="end_hour" value="<?php echo $end_hour; ?>">
                                <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-success w-100">Prenota</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">Nessuna aula disponibile per l'orario selezionato.</div>
    <?php endif; ?>
<?php endif; ?>

<?php include 'footer.php'; ?>
