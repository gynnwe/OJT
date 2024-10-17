<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ictmms";

try {
    // Connect to the database
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch all equipment records from the database
    $sql = "
        SELECT 
            equipment.equipment_id,
            equipment.equip_name,
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
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ICT Equipment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>ICT Equipment</h1>
        <div class="row mt-4">
            <div class="col-md-4 mb-3">
                <a href="equipment_input_ict.php" class="btn btn-primary w-100 py-4">
                    <i class="bi bi-plus-circle"></i> Add Equipment
                </a>
            </div>
            <div class="col-md-4 mb-3">
                <a href="equipment_maintenance.php" class="btn btn-success w-100 py-4">
                    <i class="bi bi-wrench"></i> Equipment Maintenance
                </a>
            </div>
            <div class="col-md-4 mb-3">
                <a href="plan_maintenance.php" class="btn btn-warning w-100 py-4">
                    <i class="bi bi-calendar"></i> Plan Maintenance
                </a>
            </div>
        </div>

        <!-- Search Filter Section -->
        <div class="row mt-5">
            <div class="col-md-4">
                <select id="filter-column" class="form-select">
                    <option value="0">Equipment ID</option>
                    <option value="1">Equipment Name</option>
                    <option value="2">Location ID</option>
                    <option value="3">Equipment Type</option>
                    <option value="4">Model Name</option>
                    <option value="5">Property Number</option>
                    <option value="6">Status</option>
                    <option value="7">Date Purchased</option>
                </select>
            </div>
            <div class="col-md-8">
                <input type="text" id="search-input" class="form-control" placeholder="Search...">
            </div>
        </div>

        <!-- Display Equipment Table -->
        <h2 class="mt-5">Equipment List</h2>
        <table class="table table-bordered" id="equipment-table">
            <thead>
                <tr>
                    <th>Equipment ID</th>
                    <th>Equipment Name</th>
                    <th>Location ID</th>
                    <th>Equipment Type</th>
                    <th>Model Name</th>
                    <th>Property Number</th>
                    <th>Status</th>
                    <th>Date Purchased</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($equipments as $equipment): ?>
                    <tr>
                        <td><?= $equipment['equipment_id']; ?></td>
                        <td><?= $equipment['equip_name']; ?></td>
                        <td><?= $equipment['location_id']; ?></td>
                        <td><?= $equipment['equipment_type_name']; ?></td>
                        <td><?= $equipment['model_name']; ?></td>
                        <td><?= $equipment['property_num']; ?></td>
                        <td><?= $equipment['status']; ?></td>
                        <td><?= $equipment['date_purchased']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
$(document).ready(function() {
    // Search filter logic with exact matching
    $('#search-input').on('input', function() {
        var input = $(this).val().toLowerCase().trim();
        var column = $('#filter-column').val();

        $('#equipment-table tbody tr').each(function() {
            var cellText = $(this).find('td').eq(column).text().toLowerCase().trim();

            // Toggle row visibility based on exact match
            if (input === "" || cellText === input) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
});

    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
