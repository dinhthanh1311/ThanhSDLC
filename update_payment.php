<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit("Unauthorized");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_payment'])) {
    $booking_id = (int)$_POST['booking_id'];
    $method = $_POST['method'];

    if (in_array($method, ['cash', 'banking'])) {
        $db_method = $method === 'banking' ? 'bank_transfer' : 'cash';
        
        $stmt = $pdo->prepare("UPDATE payments SET payment_method = ? WHERE booking_id = ?");
        $stmt->execute([$db_method, $booking_id]);

        echo "Success";
    }
}
?>
