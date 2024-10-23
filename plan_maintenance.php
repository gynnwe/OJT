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

    // Fetch equipment types for the dropdown
    $query = "SELECT equip_type_id, equip_type_name FROM equipment_type WHERE deleted_id = 0";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $equipmentTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $selectedEquipmentType = $_POST['equipment_type'];
        $percentages = $_POST['percentages']; // Array of percentages for each month

        if ($selectedEquipmentType && !empty($percentages)) {
            echo '<div class="container mt-5"><div class="row"><div class="col-md-12">';
            echo '<div class="card">';
            echo '<div class="card-header bg-primary text-white"><h4>Maintenance Plan Progress</h4></div>';
            echo '<div class="card-body">';

            // Loop through each month and calculate progress
            for ($month = 1; $month <= 12; $month++) {
                $percentage = intval($percentages[$month]);

                if ($percentage >= 0 && $percentage <= 100) {
                    // SQL to count serviceable equipment with history based on selected equipment type and month
                    $sqlCount = "
                        SELECT COUNT(DISTINCT e.equipment_id) as total_serviceable_with_history 
                        FROM equipment e
                        JOIN ict_maintenance_logs ml ON e.equipment_id = ml.equipment_id
                        WHERE e.status = 'Serviceable' 
                        AND YEAR(e.date_added) = :currentYear
                        AND MONTH(ml.maintenance_date) = :selectedMonth
                        AND e.equip_type_id = :selectedEquipmentType";
                    
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
                        AND YEAR(e.date_added) = :currentYear
                        AND e.equip_type_id = :selectedEquipmentType";
                    
                    $stmtList = $conn->prepare($sqlList);
                    $stmtList->bindParam(':currentYear', $currentYear, PDO::PARAM_INT);
                    $stmtList->bindParam(':selectedEquipmentType', $selectedEquipmentType);
                    $stmtList->execute();

                    $serviceableEquipment = $stmtList->fetch(PDO::FETCH_ASSOC);
                    $totalServiceable = $serviceableEquipment['total_serviceable'];

                    // Compute progress
                    $progress = ($totalServiceable * $percentage) / 100;

                    // Display the computed progress for the month
                    echo "<h5>Month: " . date('F', mktime(0, 0, 0, $month, 1)) . "</h5>";
                    echo "<p>Plan: " . htmlspecialchars($percentage) . "%</p>";
                    echo "<p>Actual: " . htmlspecialchars($progress) . " out of " . htmlspecialchars($totalServiceable) . " serviceable equipment.</p>";
                    echo "<p>Actual: " . htmlspecialchars($totalServiceableWithHistory) . " - number of equipment maintained.</p><hr>";
                } else {
                    echo "Invalid percentage for month " . $month . ".<br>";
                }
            }
            
            echo '</div></div></div></div></div>';
        } else {
            echo '<div class="alert alert-danger" role="alert">Please select an equipment type and enter percentages for all months.</div>';
        }
    } else {
        // Form for entering percentages for each month
        echo '<div class="container mt-5">
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h4>Equipment Maintenance Plan</h4>
                            </div>
                            <div class="card-body">
                                <form method="post" action="">
                                    <div class="form-group">
                                        <label for="equipment_type">Select Equipment Type:</label>
                                        <select name="equipment_type" id="equipment_type" class="form-control" required>
                                            <option value="">--Select Equipment Type--</option>';
        
        foreach ($equipmentTypes as $type) {
            echo '<option value="' . htmlspecialchars($type['equip_type_id']) . '">' . htmlspecialchars($type['equip_type_name']) . '</option>';
        }

        echo '                      </select>
                                    </div><br>';

        // Generate input fields for each month
        for ($month = 1; $month <= 12; $month++) {
            echo '<div class="form-group">
                    <label for="percentage' . $month . '">' . date('F', mktime(0, 0, 0, $month, 1)) . ' Percentage (0-100):</label>
                    <input type="number" name="percentages[' . $month . ']" id="percentage' . $month . '" class="form-control" min="0" max="100" required>
                  </div><br>';
        }

        echo '              <button type="submit" class="btn btn-primary btn-block">Submit</button>
                          </form>
                      </div>
                  </div>
              </div>
          </div>
      </div>';
    }

} catch (PDOException $e) {
    echo '<div class="alert alert-danger" role="alert">Connection failed: ' . $e->getMessage() . '</div>';
}

// Close the connection
$conn = null;
?>
