<?php
// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ictmms"; // Specify your database name here

try {
    // Create connection
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get the current year
    $currentYear = date('Y');
    echo "Current Year: " . $currentYear . "<br>";

    // Fetch equipment types for the dropdown
    $query = "SELECT equip_type_id, equip_type_name FROM equipment_type WHERE deleted_id = 0";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $equipmentTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle form submission
    $formSubmitted = false;
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $selectedEquipmentType = $_POST['equipment_type'];
        $percentages = $_POST['percentages']; // Array of percentages for each month

        if ($selectedEquipmentType && !empty($percentages)) {
            $formSubmitted = true; // Set flag to true to indicate form has been submitted
            // Loop through each month and calculate progress
            for ($month = 1; $month <= 12; $month++) {
                $percentage = intval($percentages[$month]);

                if ($percentage >= 0 && $percentage <= 100) {
                    // SQL to count serviceable equipment with history based on selected equipment type and the month
                    $sqlCount = "
                        SELECT COUNT(DISTINCT e.equipment_id) as total_serviceable_with_history 
                        FROM equipment e
                        JOIN ict_maintenance_logs ml ON e.equipment_id = ml.equipment_id
                        WHERE e.status = 'Serviceable' 
                        AND e.equip_type_id = :selectedEquipmentType
                        AND YEAR(ml.maintenance_date) = :currentYear
                        AND MONTH(ml.maintenance_date) = :selectedMonth";
                    
                    // Prepare the count query
                    $stmtCount = $conn->prepare($sqlCount);
                    $stmtCount->bindParam(':currentYear', $currentYear, PDO::PARAM_INT);
                    $stmtCount->bindParam(':selectedMonth', $month, PDO::PARAM_INT);
                    $stmtCount->bindParam(':selectedEquipmentType', $selectedEquipmentType);
                    $stmtCount->execute();

                    // Fetch result
                    $resultCount = $stmtCount->fetch(PDO::FETCH_ASSOC);
                    $totalServiceableWithHistory = $resultCount['total_serviceable_with_history'];

                    // Fetch serviceable equipment count for progress calculation
                    $sqlList = "
                        SELECT COUNT(*) as total_serviceable 
                        FROM equipment e 
                        WHERE e.status = 'Serviceable' 
                        AND e.equip_type_id = :selectedEquipmentType
                        AND YEAR(e.date_added) = :currentYear";
                    
                    $stmtList = $conn->prepare($sqlList);
                    $stmtList->bindParam(':currentYear', $currentYear, PDO::PARAM_INT);
                    $stmtList->bindParam(':selectedEquipmentType', $selectedEquipmentType);
                    $stmtList->execute();

                    $serviceableEquipment = $stmtList->fetch(PDO::FETCH_ASSOC);
                    $totalServiceable = $serviceableEquipment['total_serviceable'];

                    // Compute the planned progress
                    $plannedProgress = ($totalServiceable * $percentage) / 100;

                    // Compute the actual percentage
                    $actualPercentage = 0;
                    if ($totalServiceable > 0) {
                        $actualPercentage = ($totalServiceableWithHistory / $totalServiceable) * 100;
                    }

                    // Display the progress for the specific month
                    echo "Month: " . date('F', mktime(0, 0, 0, $month, 1)) . "<br>";
                    echo "Planned Maintenance: " . htmlspecialchars($percentage) . "%<br>";
                    echo "Total Serviceable Equipment: " . htmlspecialchars($totalServiceable) . "<br>";
                    echo "Equipment Maintained: " . htmlspecialchars($totalServiceableWithHistory) . "<br>";
                    echo "Actual Maintenance: " . number_format($actualPercentage, 2) . "%<br><br>";
                } else {
                    echo "Invalid percentage for month " . $month . ".<br>";
                }
            }
        } else {
            echo "Please select an equipment type and enter percentages for all months.<br>";
        }
    }

    // Display form only if it has not been submitted
    if (!$formSubmitted) {
        echo '<form method="post" action="">
                <label for="equipment_type">Select Equipment Type:</label>
                <select name="equipment_type" id="equipment_type" required>
                    <option value="">--Select Equipment Type--</option>';

        foreach ($equipmentTypes as $type) {
            echo '<option value="' . htmlspecialchars($type['equip_type_id']) . '">' . htmlspecialchars($type['equip_type_name']) . '</option>';
        }

        echo '  </select><br><br>';

        // Generate input fields for each month
        for ($month = 1; $month <= 12; $month++) {
            echo '<label for="percentage' . $month . '">' . date('F', mktime(0, 0, 0, $month, 1)) . ' Percentage (0-100):</label>
                  <input type="number" name="percentages[' . $month . ']" id="percentage' . $month . '" min="0" max="100" required><br><br>';
        }

        echo '<input type="submit" value="Submit">
              </form>';
    }

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

// Close the connection
$conn = null;
?>
