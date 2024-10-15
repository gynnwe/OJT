<?php
$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "ictmms";

try {
    // Create connection
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Query to fetch non-serviceable equipment
    $sql = "SELECT e.equipment_id, e.property_num, e.status, l.college, l.office, l.unit 
            FROM equipment e 
            JOIN location l ON e.location_id = l.location_id 
            WHERE e.status = 'Non-serviceable'";

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    // Fetch all non-serviceable equipment
    $nonServiceableEquipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
    <title>Profile</title>
    <link rel="stylesheet" href="path/to/your/styles.css"> <!-- Add your CSS file here -->
</head>
<body>
    <h1>Profile Page</h1>
    
    <!-- Archive Button -->
    <button id="archiveButton">Archive</button>

    <!-- Archive Section -->
    <div id="archiveSection" style="display: none;">
        <h2>Non-Serviceable Equipment</h2>
        <?php if (!empty($nonServiceableEquipment)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Equipment ID</th>
                        <th>Property Number</th>
                        <th>Status</th>
                        <th>College</th>
                        <th>Office</th>
                        <th>Unit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($nonServiceableEquipment as $equipment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($equipment['equipment_id']); ?></td>
                            <td><?php echo htmlspecialchars($equipment['property_num']); ?></td>
                            <td><?php echo htmlspecialchars($equipment['status']); ?></td>
                            <td><?php echo htmlspecialchars($equipment['college']); ?></td>
                            <td><?php echo htmlspecialchars($equipment['office']); ?></td>
                            <td><?php echo htmlspecialchars($equipment['unit']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No non-serviceable equipment found.</p>
        <?php endif; ?>
    </div>

    <script>
        // Show/Hide Archive Section
        document.getElementById("archiveButton").onclick = function() {
            var archiveSection = document.getElementById("archiveSection");
            if (archiveSection.style.display === "none") {
                archiveSection.style.display = "block";
                this.innerText = "Hide Archive";
            } else {
                archiveSection.style.display = "none";
                this.innerText = "Archive";
            }
        };
    </script>
</body>
</html>
