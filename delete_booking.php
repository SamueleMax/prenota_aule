<?php
require 'config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = $_POST['booking_id'] ?? null;
    
    if ($booking_id) {
        // Only allow user to delete their own booking, or admin to delete any
        if ($_SESSION['role'] === 'admin') {
            $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
            $stmt->execute([$booking_id]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ? AND user_id = ?");
            $stmt->execute([$booking_id, $_SESSION['user_id']]);
        }
    }
}

// Redirect back to whence they came, or index
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
exit;
