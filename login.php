<?php
require 'config.php';

// Mock Login Logic for prototype
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    
    // Simple fetch without password hashing for this demo as requested (SSO placeholder)
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email'] = $user['email'];
        header('Location: index.php');
        exit;
    } else {
        $error = "Utente non trovato.";
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Prenota Aule</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .login-card { max-width: 400px; width: 100%; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="card login-card p-4">
        <h3 class="text-center mb-4 text-primary">Prenota Aule</h3>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Email</label>
                <!-- Pre-filled for demo convenience -->
                <select name="email" class="form-select">
                    <option value="teacher@school.com">Entra come Docente (teacher@school.com)</option>
                    <option value="admin@school.com">Entra come Admin (admin@school.com)</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary w-100">Accedi</button>
        </form>
        <div class="mt-3 text-center text-muted small">
            * SSO Login simulato per demo
        </div>
    </div>
</body>
</html>
