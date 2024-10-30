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

    // Fetch all non-deleted equipment types for the dropdown
    $sql = "SELECT equip_type_id, equip_type_name FROM equipment_type WHERE deleted_id = 0";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $equipment_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all non-deleted locations for the dropdown
    $sql = "SELECT location_id, college, office, unit FROM location WHERE deleted_id = 0";  // Updated query
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle AJAX request for fetching models based on equipment type
    if (isset($_GET['equip_type_id'])) {
        $equip_type_id = $_GET['equip_type_id'];
        $sqlModels = "SELECT model_id, model_name FROM model WHERE equip_type_id = :equip_type_id AND (deleted_id IS NULL OR deleted_id = 0)";
        $stmt = $conn->prepare($sqlModels);
        $stmt->bindParam(':equip_type_id', $equip_type_id);
        $stmt->execute();
        $models = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        echo json_encode($models);
        exit;
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ICT Equipment</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.3/font/bootstrap-icons.min.css" rel="stylesheet">
	<script src="scripts.js" defer ></script>
    <style>
 /* General Styling for Add Equipment Form */
body {
    font-family: Arial, sans-serif;
    background-color: #f9f9f9;
    color: #333;
    margin: 0;
    padding: 0;
}

.container {
    max-width: 600px;
    background-color: #fff;
    border-radius: 8px;
    padding: 10px; 
    box-shadow: 0px 3px 8px rgba(0, 0, 0, 0.1);
    margin: 20px auto; 
}

label {
    font-weight: bold;
    margin-bottom: 3px;
}

.form-label {
    display: block;
    font-size: 0.85rem; 
    margin-top: 4px; 
    color: #444;
}

.form-select, .form-control {
    display: block;
    width: 100%;
    padding: 5px; 
    font-size: 0.8rem; 
    line-height: 1.2; 
    color: #495057;
    background-color: #fff;
    background-clip: padding-box;
    border: 1px solid #ced4da;
    border-radius: 4px;
    box-shadow: inset 0px 1px 1px rgba(0, 0, 0, 0.1);
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-select:focus, .form-control:focus {
    border-color: #80bdff;
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

button[type="submit"], .btn-primary {
    display: inline-block;
    font-weight: 600;
    color: #fff;
    text-align: center;
    vertical-align: middle;
    cursor: pointer;
    background-color: #b51d29;
    border: 1px solid #b51d29;
    padding: 6px 10px; 
    font-size: 0.8rem; 
    border-radius: 4px;
    transition: background-color 0.15s ease-in-out;
    margin-top: 8px; 
}

button[type="submit"]:hover, .btn-primary:hover {
    background-color: #a31723;
    border-color: #a31723;
}

button[type="submit"]:focus, .btn-primary:focus {
    box-shadow: 0 0 0 0.2rem rgba(181, 29, 41, 0.25);
    outline: 0;
}

button[type="button"], .btn-secondary {
    background-color: #6c757d;
    color: #fff;
    border: 1px solid #6c757d;
    padding: 6px 10px; 
    font-size: 0.8rem; 
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.15s ease-in-out;
    margin-top: 8px; 
}

button[type="button"]:hover, .btn-secondary:hover {
    background-color: #5a6268;
    border-color: #545b62;
}

.mb-3 {
    margin-bottom: 8px; 
}

input[type="text"], input[type="date"] {
    padding: 6px; 
    font-size: 0.8rem; 
    border-radius: 4px;
    border: 1px solid #ced4da;
    width: calc(100% - 12px); 
}

select {
    padding: 6px; 
    font-size: 0.8rem; 
    border-radius: 4px;
    border: 1px solid #ced4da;
    width: calc(100% - 12px); 
}

/* Rounded Corners for Cards */
.card {
    border-radius: 8px;
    box-shadow: 0px 3px 8px rgba(0, 0, 0, 0.1);
}

/* Styling for Add/Cancel buttons */
.btn {
    border-radius: 6px;
    padding: 6px 10px; 
    font-size: 0.8rem;
    font-weight: bold;
}

.btn-add {
    background-color: #b51d29;
    color: #fff;
}

.btn-cancel {
    background-color: #6c757d;
    color: #fff;
}

.btn-add:hover {
    background-color: #a31723;
}

.btn-cancel:hover {
    background-color: #5a6268;
}

.btn-add:focus, .btn-cancel:focus {
    box-shadow: 0 0 0 0.2rem rgba(108, 117, 125, 0.5);
}
    </style>
</head>
<body>
    <div class="container mt-5">
        <!-- Add Equipment Form -->
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>
        
        <form action="equipment_process.php" method="POST">
            <div class="mb-3">
                <label for="location_id" class="form-label">Location:</label>
                <select name="location_id" id="location_id" class="form-select" required>
                    <option value="">Select a location</option>
                    <?php if (!empty($locations)): ?>
                        <?php foreach ($locations as $location): ?>
                            <option value="<?php echo htmlspecialchars($location['location_id']); ?>">
                                <?php echo htmlspecialchars($location['college'] . " - " . $location['office'] . " - " . $location['unit']); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="">No locations available</option>
                    <?php endif; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="equipment_type" class="form-label">Equipment Type:</label>
                <select name="equipment_type" id="equipment_type" class="form-select" required>
                    <option value="">Select an equipment type</option>
                    <?php if (!empty($equipment_types)): ?>
                        <?php foreach ($equipment_types as $type): ?>
                            <option value="<?php echo htmlspecialchars($type['equip_type_id']); ?>">
                                <?php echo htmlspecialchars($type['equip_type_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="">No equipment types available</option>
                    <?php endif; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="equip_name" class="form-label">Equipment Name:</label>
                <input type="text" name="equip_name" id="equip_name" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="property_num" class="form-label">Property Number:</label>
                <input type="text" name="property_num" id="property_num" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="model_name" class="form-label">Model Name:</label>
                <select name="model_id" id="model_name" class="form-select" required>
                    <!-- Options will be populated dynamically based on selected equipment type -->
                </select>
            </div>

            <div class="mb-3">
                <label for="status" class="form-label">Status:</label>
                <select name="status" id="status" class="form-select" required>
                    <option value="Serviceable">Serviceable</option>
                    <option value="Non-serviceable">Non-serviceable</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="date_purchased" class="form-label">Date Purchased:</label>
                <input type="date" name="date_purchased" id="date_purchased" class="form-control" required>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    </div>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
