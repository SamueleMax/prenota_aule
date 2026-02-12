<?php
require 'config.php';
requireLogin();
include 'header.php';

$selected_room_id = $_GET['room_id'] ?? null;
$selected_date = $_GET['date'] ?? date('Y-m-d');

// Fetch enabled rooms for dropdown
$rooms_stmt = $pdo->query("SELECT * FROM rooms WHERE is_enabled = 1 ORDER BY name ASC");
$rooms = $rooms_stmt->fetchAll();

// If room is selected, fetch availability
$schedule = [];
if ($selected_room_id) {
    // Get bookings for the selected date
    $stmt = $pdo->prepare("
        SELECT * FROM bookings 
        WHERE room_id = ? AND booking_date = ?
    ");
    $stmt->execute([$selected_room_id, $selected_date]);
    $bookings = $stmt->fetchAll();

    // Map bookings to a simple busy array [hour => booking_info]
    $busy_slots = [];
    foreach ($bookings as $b) {
        for ($h = $b['start_hour']; $h <= $b['end_hour']; $h++) {
            $busy_slots[$h] = $b;
        }
    }

    // Build schedule for 1-8 hours
    for ($h = 1; $h <= 8; $h++) {
        if (isset($busy_slots[$h])) {
            $schedule[$h] = [
                'status' => 'busy',
                'details' => $busy_slots[$h]
            ];
        } else {
            $schedule[$h] = [
                'status' => 'free'
            ];
        }
    }
}
?>

<div class="row">
    <div class="col-md-12">
        <h2>Disponibilità per Aula</h2>
        <p class="text-muted">Seleziona un'aula per vedere il suo calendario giornaliero.</p>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label">Aula</label>
                <select name="room_id" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Seleziona Aula --</option>
                    <?php foreach($rooms as $room): ?>
                        <option value="<?php echo $room['id']; ?>" <?php echo $selected_room_id == $room['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($room['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label">Data</label>
                <input type="date" name="date" class="form-control" value="<?php echo $selected_date; ?>" onchange="this.form.submit()">
            </div>
            <div class="col-md-2">
                <a href="book_by_room.php" class="btn btn-outline-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<?php if ($selected_room_id && !empty($schedule)): ?>
    <div class="card">
        <div class="card-header bg-light">
            <strong>Orari per il <?php echo date('d/m/Y', strtotime($selected_date)); ?></strong>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="table-secondary">
                        <tr>
                            <th>Ora</th>
                            <th>Orario</th>
                            <th>Stato</th>
                            <th>Azione</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($schedule as $hour => $slot): ?>
                            <tr class="<?php echo $slot['status'] === 'busy' ? 'table-danger' : 'table-success'; ?>">
                                <td style="width: 50px;" class="fw-bold"><?php echo $hour; ?>°</td>
                                <td style="width: 150px;"><?php echo $hours_mapping[$hour]; ?></td>
                                <td>
                                    <?php if($slot['status'] === 'busy'): ?>
                                        <span class="badge bg-danger">Occupata</span>
                                        <?php if($_SESSION['role'] === 'admin'): ?>
                                            <small class="d-block text-muted">Prenotato da User #<?php echo $slot['details']['user_id']; ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-success">Libera</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($slot['status'] === 'free' && strtotime($selected_date . ' ' . explode(' - ', $hours_mapping[$hour])[0]) > time()): ?>
                                        <!-- Only allow booking if slot is free and in future (ish) -->
                                        <form action="book_by_time.php" method="POST">
                                            <input type="hidden" name="room_id" value="<?php echo $selected_room_id; ?>">
                                            <input type="hidden" name="date" value="<?php echo $selected_date; ?>">
                                            <input type="hidden" name="start_hour" value="<?php echo $hour; ?>">
                                            <input type="hidden" name="end_hour" value="<?php echo $hour; ?>">
                                            <button type="submit" class="btn btn-sm btn-primary">Prenota Ora</button>
                                        </form>
                                    <?php elseif($slot['status'] === 'busy' && ($_SESSION['role'] === 'admin' || $slot['details']['user_id'] == $_SESSION['user_id'])): ?>
                                        <!-- Allow cancel if admin or owner -->
                                        <form action="delete_booking.php" method="POST" onsubmit="return confirm('Cancellare prenotazione?');">
                                            <input type="hidden" name="booking_id" value="<?php echo $slot['details']['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Libera</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php elseif($selected_room_id): ?>
    <p class="text-muted">Seleziona una data valida.</p>
<?php endif; ?>

<?php include 'footer.php'; ?>
