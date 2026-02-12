<?php
session_start();

$host = 'localhost';
$db   = 'prenota_aule3';
$user = 'showbiz'; // Default XAMPP/MAMP user often root with no pass
$pass = 'showbiz'; // Default password often empty
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Ideally log this
    die("Database connection failed. Please ensure the database 'prenota_aule' exists and credentials are correct in config.php. <br>Error: " . $e->getMessage());
}

// Helper to check login
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        die("Accesso negato. Area riservata agli amministratori.");
    }
}

// School hours mapping
$hours_mapping = [
    1 => '08:00 - 09:00',
    2 => '09:00 - 10:00',
    3 => '10:00 - 11:00',
    4 => '11:00 - 12:00',
    5 => '12:00 - 13:00',
    6 => '13:00 - 14:00',
    7 => '14:00 - 15:00',
    8 => '15:00 - 16:00',
];
?>
