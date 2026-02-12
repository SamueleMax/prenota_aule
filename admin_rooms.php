<?php
require 'config.php';
requireAdmin(); // Protect this page
include 'header.php';

$msg = '';

// Handle Add Room
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = trim($_POST['name']);
    if ($name) {
        try {
            $stmt = $pdo->prepare("INSERT INTO rooms (name, is_enabled) VALUES (?, 1)");
            $stmt->execute([$name]);
            $msg = "<div class='alert alert-success'>Aula aggiunta con successo.</div>";
        } catch (PDOException $e) {
            $msg = "<div class='alert alert-danger'>Errore: l'aula esiste già?</div>";
        }
    }
}

// Handle Toggle Status
if (isset($_GET['toggle'])) {
    $id = $_GET['toggle'];
    $current = $_GET['status'];
    $new = $current ? 0 : 1;
    $stmt = $pdo->prepare("UPDATE rooms SET is_enabled = ? WHERE id = ?");
    $stmt->execute([$new, $id]);
    header('Location: admin_rooms.php');
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: admin_rooms.php');
    exit;
}

$rooms = $pdo->query("SELECT * FROM rooms ORDER BY name ASC")->fetchAll();
?>

<h3>Gestione Aule</h3>
<?php echo $msg; ?>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Aggiungi Nuova Aula</div>
            <div class="card-body">
                <form method="POST" class="d-flex gap-2">
                    <input type="hidden" name="action" value="add">
                    <input type="text" name="name" class="form-control" placeholder="Nome Aula (es. A12)" required>
                    <button type="submit" class="btn btn-primary">Aggiungi</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Stato</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($rooms as $r): ?>
                    <tr>
                        <td><?php echo $r['id']; ?></td>
                        <td><?php echo htmlspecialchars($r['name']); ?></td>
                        <td>
                            <?php if($r['is_enabled']): ?>
                                <span class="badge bg-success">Attiva</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Manutenzione</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="?toggle=<?php echo $r['id']; ?>&status=<?php echo $r['is_enabled']; ?>" class="btn btn-sm btn-warning">
                                <?php echo $r['is_enabled'] ? 'Disabilita' : 'Abilita'; ?>
                            </a>
                            <a href="?delete=<?php echo $r['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Sicuro?');">Elimina</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>
