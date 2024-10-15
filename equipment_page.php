<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ICT Equipment</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>ICT Equipment</h1>
        <div class="row mt-4">
            <!-- Add Equipment Button -->
            <div class="col-md-4 mb-3">
                <a href="equipment_input_ict.php" class="btn btn-primary w-100 py-4">
                    <i class="bi bi-plus-circle"></i> Add Equipment
                </a>
            </div>
            <!-- Equipment Maintenance Button -->
            <div class="col-md-4 mb-3">
                <a href="equipment_maintenance.php" class="btn btn-success w-100 py-4">
                    <i class="bi bi-wrench"></i> Equipment Maintenance
                </a>
            </div>
            <!-- Plan Maintenance Button -->
            <div class="col-md-4 mb-3">
                <a href="plan_maintenance.php" class="btn btn-warning w-100 py-4">
                    <i class="bi bi-calendar"></i> Plan Maintenance
                </a>
            </div>
        </div>

        <!-- Display Equipment Table -->
        <h2 class="mt-5">Equipment List</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Equipment ID</th>
                    <th>Location ID</th>
                    <th>Equipment Type</th>
                    <th>Model Name</th>
                    <th>Property Number</th>
                    <th>Status</th>
                    <th>Date Purchased</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $servername = "localhost";
                $username = "root";
                $password = "";
                $dbname = "ictmms";

                try {
                    // Connect to the database
                    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
                    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                    // Fetch all equipment from the database with joined tables for names
					$sql = "
						SELECT 
							equipment.equipment_id, 
							equipment.location_id, 
							equipment_type.equip_type_name AS equipment_type_name, 
							model.model_name AS model_name, 
							equipment.property_num, 
							equipment.status, 
							equipment.date_purchased 
						FROM equipment
						JOIN equipment_type ON equipment.equip_type_id = equipment_type.equip_type_id  
						JOIN model ON equipment.model_id = model.model_id";
                    
                    $stmt = $conn->prepare($sql);
                    $stmt->execute();
                    $equipments = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    // Display each equipment in a table row
                    foreach ($equipments as $equipment) {
                        echo "<tr>
                                <td>{$equipment['equipment_id']}</td>
                                <td>{$equipment['location_id']}</td>
                                <td>{$equipment['equipment_type_name']}</td>
                                <td>{$equipment['model_name']}
                                <td>{$equipment['property_num']}</td>
                                <td>{$equipment['status']}</td>
                                <td>{$equipment['date_purchased']}</td>
                              </tr>";
                    }
                } catch (PDOException $e) {
                    echo "Error: " . $e->getMessage();
                }

                $conn = null;
                ?>
            </tbody>
        </table>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
