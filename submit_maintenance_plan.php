<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ictmms";

// Create connection
$conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $planId = $_POST['plan_id'];

    // Update the status to 'submitted'
    $stmt = $conn->prepare("UPDATE maintenance_plan SET status = 'submitted' WHERE id = ?");
    $stmt->execute([$planId]);

    // Redirect back to the maintenance plans page
    header('Location: plan_maintenance.php');
    exit();
}
?>
