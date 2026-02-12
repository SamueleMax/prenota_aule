<?php
require 'config.php';
requireLogin();
include 'header.php';

// Fetch users bookings
$stmt = $pdo->prepare("
    SELECT b.*, r.name as room_name 
    FROM bookings b 
    JOIN rooms r ON b.room_id = r.id 
    WHERE b.user_id = ? AND b.booking_date >= CURDATE()
    ORDER BY b.booking_date ASC, b.start_hour ASC
");
$stmt->execute([$_SESSION['user_id']]);
$my_bookings = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-md-12 mb-4">
        <h1 class="display-5">Benvenuto, <?php echo htmlspecialchars($_SESSION['name']); ?></h1>
        <p class="lead">Gestisci le tue prenotazioni scolastiche.</p>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-clock-history text-primary display-4 mb-3"></i>
                <h5 class="card-title">Prenota per Orario</h5>
                <p class="card-text">Hai un giorno e un'ora specifici? Cerca quali aule sono libere.</p>
                <a href="book_by_time.php" class="btn btn-primary">Cerca Aule</a>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-door-open text-success display-4 mb-3"></i>
                <h5 class="card-title">Disponibilità Aula</h5>
                <p class="card-text">Vuoi una stanza specifica? Controlla quando è libera.</p>
                <a href="book_by_room.php" class="btn btn-success">Vedi Calendario</a>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white">
                <h4 class="mb-0">Le tue prossime prenotazioni</h4>
            </div>
            <div class="card-body">
                <?php if (count($my_bookings) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Aula</th>
                                    <th>Orario</th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($my_bookings as $booking): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($booking['booking_date'])); ?></td>
                                        <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($booking['room_name']); ?></span></td>
                                        <td>
                                            <?php echo $hours_mapping[$booking['start_hour']] . ' -> ' . explode(' - ', $hours_mapping[$booking['end_hour']])[1]; ?>
                                        </td>
                                        <td>
                                            <form method="POST" action="delete_booking.php" onsubmit="return confirm('Sei sicuro di voler cancellare questa prenotazione?');">
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Cancella</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">Nessuna prenotazione futura.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
