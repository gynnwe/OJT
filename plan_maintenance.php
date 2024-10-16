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

    // Initialize progress variable
    $progress = null;
    $selectedEquipmentType = isset($_POST['equipment_type']) ? $_POST['equipment_type'] : null;

    // Ensure that the selected equipment type is provided before executing the queries
    if ($selectedEquipmentType) {
        // SQL to count serviceable equipment with history based on selected equipment type
        $sqlCount = "
            SELECT COUNT(DISTINCT e.equipment_id) as total_serviceable_with_history 
            FROM equipment e
            JOIN ict_maintenance_logs ml ON e.equipment_id = ml.equipment_id
            WHERE e.status = 'Serviceable' 
            AND YEAR(e.date_added) = :currentYear
            AND MONTH(ml.maintenance_date) = :selectedMonth
            AND e.equip_type_id = :selectedEquipmentType"; // Assuming equip_type_id is the foreign key in equipment table
    
        // Prepare SQL to fetch the list of serviceable equipment with date added based on selected equipment type
        $sqlList = "
            SELECT e.equip_name, e.date_added 
            FROM equipment e 
            WHERE e.status = 'Serviceable' 
            AND YEAR(e.date_added) = :currentYear
            AND e.equip_type_id = :selectedEquipmentType"; // Include equipment type filtering
    
    
    $stmtList = $conn->prepare($sqlList);
    $stmtList->bindParam(':currentYear', $currentYear, PDO::PARAM_INT);
    $stmtList->bindParam(':selectedEquipmentType', $selectedEquipmentType); // Bind equipment type ID
    $stmtList->execute();

    // Fetch all serviceable equipment
    $serviceableEquipment = $stmtList->fetchAll(PDO::FETCH_ASSOC);

    
    // Handle form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $selectedMonth = intval($_POST['month']); // Ensure selected month is an integer
        $percentage = $_POST['percentage'];

        // Validate input (basic validation)
        if (!empty($selectedMonth) && is_numeric($percentage) && $percentage >= 0 && $percentage <= 100) {
            // Prepare the count query with selected month
            $stmtCount = $conn->prepare($sqlCount);
            $stmtCount->bindParam(':currentYear', $currentYear, PDO::PARAM_INT);
            $stmtCount->bindParam(':selectedMonth', $selectedMonth, PDO::PARAM_INT);
            $stmtCount->bindParam(':selectedEquipmentType', $selectedEquipmentType); // Bind equipment type ID
            $stmtCount->execute();

            // Fetch the result
            $resultCount = $stmtCount->fetch(PDO::FETCH_ASSOC);
            $totalServiceableWithHistory = $resultCount['total_serviceable_with_history'];

            // Compute progress
            $progress = (count($serviceableEquipment) * $percentage) / 100;

            // Display the computed progress
            echo "Selected Month: " . htmlspecialchars($selectedMonth) . "<br>";
            echo "Plan: " . htmlspecialchars($percentage) . "%<br>";
            echo "Actual: " . htmlspecialchars($progress) . " out of " . htmlspecialchars(count($serviceableEquipment)) . " serviceable equipment.<br>";
            echo "Actual: " . htmlspecialchars($totalServiceableWithHistory) . " - number of equipment maintained."; 
        } else {
            echo "Please select a valid month and enter a percentage between 0 and 100.<br>";
        }
    }

    // Display the list of serviceable equipment
    echo "<h3>List of Serviceable Equipment for " . $currentYear . ":</h3>";
    echo "<table border='1'>
            <tr>
                <th>Equipment Name</th>
                <th>Date Added</th>
            </tr>";
    foreach ($serviceableEquipment as $equipment) {
        echo "<tr>
                <td>" . htmlspecialchars($equipment['equip_name']) . "</td>
                <td>" . htmlspecialchars($equipment['date_added']) . "</td>
              </tr>";
    }
    echo "</table>";
}

    // Fetch equipment types for the dropdown
$query = "SELECT equip_type_id, equip_type_name FROM equipment_type WHERE deleted_id = 0";
$stmt = $conn->prepare($query);
$stmt->execute();
$equipmentTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Dropdown for month selection, equipment type selection, and input field for percentage
echo '<form method="post" action="">
        <label for="month">Select Month:</label>
        <select name="month" id="month" required>
            <option value="">--Select Month--</option>
            <option value="1">January</option>
            <option value="2">February</option>
            <option value="3">March</option>
            <option value="4">April</option>
            <option value="5">May</option>
            <option value="6">June</option>
            <option value="7">July</option>
            <option value="8">August</option>
            <option value="9">September</option>
            <option value="10">October</option>
            <option value="11">November</option>
            <option value="12">December</option>
        </select>
        <br><br>
        
        <label for="equipment_type">Select Equipment Type:</label>
        <select name="equipment_type" id="equipment_type" required>
            <option value="">--Select Equipment Type--</option>';
            
foreach ($equipmentTypes as $type) {
    echo '<option value="' . htmlspecialchars($type['equip_type_id']) . '">' . htmlspecialchars($type['equip_type_name']) . '</option>';
}

echo '      </select>
        <br><br>
        
        <label for="percentage">Enter Percentage (0-100):</label>
        <input type="number" name="percentage" id="percentage" min="0" max="100" required>
        <br><br>
        
        <input type="submit" value="Submit">
      </form>';

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

// Close the connection
$conn = null;
?>
