<?php
$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "ictmms";

try {
    // Create connection
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Query to fetch total count of serviceable equipment
    $sql = "SELECT * FROM equipment WHERE status = 'Serviceable'";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $serviceableEquipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalServiceable = count($serviceableEquipment);
    
    $maintainedEquipmentIds = [];
    $maintainedEquipment = [];
    $notMaintainedEquipment = [];

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $inputPercentage = (int)$_POST['maintenance_percentage'];

        // Query to fetch maintenance logs for the current month
        $currentMonth = date('Y-m');
        $sql = "SELECT DISTINCT equipment_id FROM ict_maintenance_logs 
                WHERE maintenance_date LIKE :currentMonth";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':currentMonth', $currentMonth . '%', PDO::PARAM_STR);
        $stmt->execute();
        $maintainedEquipmentIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Categorize equipment based on maintenance status
        foreach ($serviceableEquipment as $equipment) {
            if (in_array($equipment['equipment_id'], $maintainedEquipmentIds)) {
                $maintainedEquipment[] = $equipment;
            } else {
                $notMaintainedEquipment[] = $equipment;
            }
        }

        // Calculate percentage of maintained equipment
        if ($totalServiceable > 0) {
            $maintainedCount = count($maintainedEquipment);
            $maintenanceRate = ($maintainedCount / $totalServiceable) * 100;
        } else {
            $maintenanceRate = 0;
        }

        // Check if the maintenance rate meets the input percentage
        $isMaintained = $maintenanceRate >= $inputPercentage ? "Yes" : "No";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

$conn = null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plan Maintenance</title>
    <link rel="stylesheet" href="path/to/your/styles.css"> <!-- Add your CSS file here -->
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        h1, h2, h3, h4 {
            color: #333;
        }
        button {
            padding: 10px 15px;
            background-color: #28a745;
            color: white;
            border: none;
            cursor: pointer;
            margin-top: 10px;
        }
        button:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <h1>Plan Maintenance</h1>
    
    <h2>Total Serviceable Equipment: <?php echo htmlspecialchars($totalServiceable); ?></h2>

    <form method="POST" action="">
        <label for="maintenance_percentage">Enter the maintenance percentage:</label>
        <input type="number" id="maintenance_percentage" name="maintenance_percentage" min="0" max="100" required>
        <input type="submit" value="Check Maintenance">
    </form>

    <?php if (isset($maintenanceRate)): ?>
        <h3>Maintenance Status</h3>
        <p>Maintenance Rate: <?php echo number_format($maintenanceRate, 2) . '%'; ?></p>
        <p>Meets Required Percentage: <?php echo htmlspecialchars($isMaintained); ?></p>

        <h4>Maintained Equipment:</h4>
        <button onclick="window.location.href='maintained_equipment.php'">View Maintained Equipment</button>

        <h4>Not Maintained Equipment:</h4>
        <button onclick="window.location.href='not_maintained_equipment.php'">View Not Maintained Equipment</button>

        <h4>Percentage Progress:</h4>
        <p><?php echo number_format($maintenanceRate, 2) . '% out of 100%'; ?></p>
    <?php endif; ?>
    
</body>
</html>
