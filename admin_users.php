<?php
require 'config.php';
requireAdmin();
include 'header.php';

$msg = '';

// Handle Add User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $email = trim($_POST['email']);
    $name = trim($_POST['name']);
    $role = $_POST['role'];
    
    if ($email && $name) {
        try {
            $stmt = $pdo->prepare("INSERT INTO users (email, name, role) VALUES (?, ?, ?)");
            $stmt->execute([$email, $name, $role]);
            $msg = "<div class='alert alert-success'>Utente creato con successo.</div>";
        } catch (PDOException $e) {
            $msg = "<div class='alert alert-danger'>Errore: email già esistente?</div>";
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // Prevent delete self
    if ($id != $_SESSION['user_id']) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
    }
    header('Location: admin_users.php');
    exit;
}

$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
?>

<h3>Gestione Utenti</h3>
<?php echo $msg; ?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">Aggiungi Nuovo Utente</div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <input type="hidden" name="action" value="add">
                    <div class="col-md-4">
                        <input type="text" name="name" class="form-control" placeholder="Nome Completo" required>
                    </div>
                    <div class="col-md-4">
                        <input type="email" name="email" class="form-control" placeholder="Email (es per login)" required>
                    </div>
                    <div class="col-md-2">
                        <select name="role" class="form-select">
                            <option value="teacher">Teacher</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Crea</button>
                    </div>
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
                    <th>Email</th>
                    <th>Ruolo</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($users as $u): ?>
                    <tr>
                        <td><?php echo $u['id']; ?></td>
                        <td><?php echo htmlspecialchars($u['name']); ?></td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td><span class="badge bg-secondary"><?php echo $u['role']; ?></span></td>
                        <td>
                            <?php if($u['id'] != $_SESSION['user_id']): ?>
                                <a href="?delete=<?php echo $u['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Sicuro?');">Elimina</a>
                            <?php else: ?>
                                <span class="text-muted">(Te stesso)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>
