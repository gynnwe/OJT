<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Plan</title>
    <style>
       /* CSS for Maintenance Plan */

/* General Styles */
body {
    font-family: 'Arial', sans-serif;
    background-color: #f8f9fa;
    color: #333;
    margin: 0;
    padding: 0;
}

.container {
    max-width: 800px;
    margin: 30px auto;
    padding: 5px;
}

.card {
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    border: none;
}

.card-header {
    border-radius: 10px 10px 0 0;
    font-weight: bold;
    font-size: 1.1rem;
}

.card-body {
    padding: 20px;
}

/* Form Styles */
.form-group {
    margin-bottom: 15px;
    text-align: center;
}

label {
    font-weight: bold;
    display: block;
    margin-bottom: 3px;
}

.label-p {
    margin-top: 20px;
}

.form-control {
    border-radius: 8px;
    border: 1px solid #ced4da;
    padding: 8px;
    font-size: 0.9rem;
    width: 30%;
    margin: 0 auto;
}

select.form-control {
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
}

/* Buttons */
.btn {
    border-radius: 40px;
    padding: 8px 16px;
    text-transform: uppercase;
    font-weight: bold;
    font-size: 0.9rem;
}

.btn-primary {
    background-color: #8a1616;
    border: none;
    color: white;
}

.btn-primary:hover {
    background-color: #721414;
}

.btn-block {
    width: 100%;
}

/* Header Styles */
h4 {
    margin: 0;
    text-align: center;
    font-size: 1.2rem;
    padding: 10px;
}

/* Progress Display */
h5 {
    font-size: 1rem;
    margin-top: 15px;
    color: #495057;
}

p {
    font-size: 0.9rem;
    color: #6c757d;
    margin-bottom: 10px;
}

hr {
    border-top: 1px solid #dee2e6;
    margin-top: 15px;
    margin-bottom: 15px;
}

/* Input Focus */
.form-control:focus {
    border-color: #8a1616;
    box-shadow: 0 0 3px rgba(138, 22, 22, 0.5);
}

/* Calendar Icon for Date Picker */
input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(0.4);
}

/* Responsive Styles */
@media (max-width: 768px) {
    .container {
        margin: 15px;
    }

    .card-body {
        padding: 15px;
    }
}
    </style>
</head>
<body>
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
                                            <label class="label-p" style="text-align: center; display: block;">Percentages (10-100)</label>
                                        </div><br>';

            // Generate input fields for months grouped into rows of three
            echo '<div class="form-group">
                    <input type="number" name="percentages[1]" id="percentage1" class="form-control" placeholder="January" min="0" max="100" required>
                    <input type="number" name="percentages[2]" id="percentage2" class="form-control mt-1" placeholder="February" min="0" max="100" required>
                    <input type="number" name="percentages[3]" id="percentage3" class="form-control mt-1" placeholder="March" min="0" max="100" required>
                  </div>
                  <div class="form-group">
                    <input type="number" name="percentages[4]" id="percentage4" class="form-control" placeholder="April" min="0" max="100" required>
                    <input type="number" name="percentages[5]" id="percentage5" class="form-control mt-1" placeholder="May" min="0" max="100" required>
                    <input type="number" name="percentages[6]" id="percentage6" class="form-control mt-1" placeholder="June" min="0" max="100" required>
                  </div>
                  <div class="form-group">
                    <input type="number" name="percentages[7]" id="percentage7" class="form-control" placeholder="July" min="0" max="100" required>
                    <input type="number" name="percentages[8]" id="percentage8" class="form-control mt-1" placeholder="August" min="0" max="100" required>
                    <input type="number" name="percentages[9]" id="percentage9" class="form-control mt-1" placeholder="September" min="0" max="100" required>
                  </div>
                  <div class="form-group">
                    <input type="number" name="percentages[10]" id="percentage10" class="form-control" placeholder="October" min="0" max="100" required>
                    <input type="number" name="percentages[11]" id="percentage11" class="form-control mt-1" placeholder="November" min="0" max="100" required>
                    <input type="number" name="percentages[12]" id="percentage12" class="form-control mt-1" placeholder="December" min="0" max="100" required>
                  </div>';

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
</body>
</html>
