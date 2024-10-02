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
    $remarks = $_POST['remarks']; // Capture remarks from dropdown
    $maintaindate = $_POST['maintaindate'];

    if (isset($_POST['equipment_id'])) {
        $equipment_id = $_POST['equipment_id'];

        try {
            // Create a new PDO connection
            $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Insert into maintenance logs table
            foreach ($_POST['responsible_firstname'] as $index => $firstname) {
                // Initialize personnel_id as NULL
                $personnel_id = null;

                if (!empty($firstname)) {
                    // Fetch last name and department
                    $lastname = $_POST['responsible_lastname'][$index];
                    $department = $_POST['responsible_department'][$index];

                    // Check if remarks indicate a transfer
                    if ($remarks === 'For Transfer') {
                        // Check if personnel already exists
                        $stmtPersonnelId = $conn->prepare("SELECT personnel_id FROM personnel WHERE firstname = :firstname AND lastname = :lastname");
                        $stmtPersonnelId->execute(['firstname' => $firstname, 'lastname' => $lastname]);
                        $personnel_id_row = $stmtPersonnelId->fetch(PDO::FETCH_ASSOC);

                        if ($personnel_id_row) {
                            // Personnel exists, fetch their ID
                            $personnel_id = intval($personnel_id_row['personnel_id']);
                        } else {
                            // Personnel does not exist, insert new personnel
                            if (!empty($department)) { // Ensure department is not empty before inserting
                                $stmtInsertPersonnel = $conn->prepare("INSERT INTO personnel (firstname, lastname, department) VALUES (:firstname, :lastname, :department)");
                                $stmtInsertPersonnel->execute(['firstname' => $firstname, 'lastname' => $lastname, 'department' => $department]);
                                // Get the newly inserted personnel ID
                                $personnel_id = intval($conn->lastInsertId());
                            }
                        }
                    } 
                    // If remarks are not "For Transfer", personnel_id remains NULL and will be inserted as such in the log.
                }

                // Insert into maintenance logs table with remarks included
                try {
                    // Prepare insert statement with remarks included
                    // Use NULL for personnel_id if it was not set (i.e., not transferring)
                    $stmtInsertLog =  $conn->prepare("INSERT INTO ict_maintenance_logs (jo_number, personnel_id, equipment_id, maintenance_date, actions_taken, remarks) VALUES (?, ?, ?, ?, ?, ?)");
                    if (!$stmtInsertLog->execute([$jo_number,  $personnel_id ?? null,  $equipment_id,  $maintaindate,  $actions_taken,  $remarks])) {
                        print_r($stmtInsertLog->errorInfo()); // Output any errors during execution
                    } else {
                        echo "Log created successfully for JO Number: {$jo_number} with Personnel ID: " . ($personnel_id ?? 'NULL') . "<br>";
                    }
                } catch (PDOException $e) {
                    echo "Error inserting log: " . $e->getMessage();
                }
            }

        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
        
    } else {
        echo "No equipment selected.";
    }

    // Close connection
    if ($conn) {
        unset($conn); // Close connection properly by unsetting it.
    }
}
?>