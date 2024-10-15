<?php
error_reporting(E_ALL); // Report all types of errors
ini_set('display_errors', 1); // Display errors on the screen

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ictmms";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data
    $jo_number = $_POST['jo_number'];
    $actions_taken = $_POST['actions_taken'];
    $remarks_id = $_POST['remarks'];
    $maintaindate = $_POST['maintaindate'];

    if (isset($_POST['equipment_id'])) {
        $equipment_id = $_POST['equipment_id'];

        try {
            // Create a new PDO connection
            $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Check if personnel is selected from dropdown
            if (isset($_POST['personnel_id']) && !empty($_POST['personnel_id'])) {
                $personnel_id = intval($_POST['personnel_id']); // Get selected personnel ID

                try {
                    // Insert into maintenance logs table with remarks_id included
                    $stmtInsertLog = $conn->prepare("INSERT INTO ict_maintenance_logs (jo_number, personnel_id, equipment_id, maintenance_date, actions_taken, remarks_id) VALUES (?, ?, ?, ?, ?, ?)");
                    if (!$stmtInsertLog->execute([$jo_number, $personnel_id, $equipment_id, $maintaindate, $actions_taken, $remarks_id])) {
                        print_r($stmtInsertLog->errorInfo()); 
                    } else {
                        echo "Log created successfully for JO Number: {$jo_number} with Personnel ID: {$personnel_id}<br>";
                    }
                } catch (PDOException $e) {
                    echo "Error inserting log: " . $e->getMessage();
                }
            } else {
                echo "No personnel selected.";
            }

        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }

    } else {
        echo "No equipment selected.";
    }

    // Close connection properly by setting to null
    if (isset($conn)) {
        $conn = null; 
    }
}
?>