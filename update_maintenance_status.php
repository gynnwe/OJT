<?php
// update_status.php
session_start();
include 'conn.php';

// Create connection
$conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['plan_id']) && isset($_POST['status'])) {
    $plan_id = filter_var($_POST['plan_id'], FILTER_VALIDATE_INT);
    $status = filter_var($_POST['status'], FILTER_SANITIZE_STRING);
    
    // Validate the status
    $allowed_statuses = ['archive', 'trash', 'pending']; // Added 'pending' to allowed statuses
    if (!in_array($status, $allowed_statuses)) {
        $_SESSION['error'] = "Invalid status provided.";
        header("Location: plan_maintenance.php");
        exit();
    }

    try {
        $stmt = $conn->prepare("UPDATE maintenance_plan SET status = ? WHERE id = ?");
        $stmt->execute([$status, $plan_id]);
        
        $_SESSION['success'] = "Maintenance plan status updated successfully.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error updating maintenance plan status.";
    }
} else {
    $_SESSION['error'] = "Invalid request.";
}

header("Location: plan_maintenance.php");
exit();