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
    $sql = "SELECT location_id, college, office, unit FROM location WHERE deleted = 0";  // Updated query
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
                <a href="#" class="btn btn-warning w-100 py-4">
                    <i class="bi bi-calendar"></i> Plan Maintenance
                </a>
            </div>
        </div>

        <!-- Add Equipment Form -->
        <h2 class="mt-5">Add Equipment</h2>
        <?php
        // To show any error messages
        if (isset($_GET['error'])) {
            echo "<p style='color:red;'>" . $_GET['error'] . "</p>";
        }
        ?>
        <form action="equipment_process.php" method="POST">
            <label for="location_id">Location:</label>
            <select name="location_id" id="location_id" required>
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
            </select><br>

            <label for="equipment_type">Equipment Type:</label>
            <select name="equipment_type" id="equipment_type" required>
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
            </select><br>

            <label for="property_num">Property Number:</label>
            <input type="text" name="property_num" id="property_num" required><br>

            <label for="model_name">Model Name:</label>
            <select name="model_id" id="model_name" required>
                <!-- Options will be populated dynamically based on selected equipment type -->
            </select><br>

            <label for="status">Status:</label>
            <select name="status" id="status" required>
                <option value="Serviceable">Serviceable</option>
                <option value="Non-serviceable">Non-serviceable</option>
            </select><br>

            <label for="date_purchased">Date Purchased:</label>
            <input type="date" name="date_purchased" id="date_purchased" required><br>

            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
    // JavaScript code to handle dynamic model population
    $(document).ready(function() {
        $('#equipment_type').change(function() {
            var equipTypeId = $(this).val();
            
            // Clear previous models
            $('#model_name').empty();

            if (equipTypeId) {
                $.ajax({
                    url: 'equipment_input_ict.php', 
                    type: 'GET',
                    data: { equip_type_id: equipTypeId },
                    success: function(data) {
                        var models = JSON.parse(data);
                        if (models.length > 0) {
                            $.each(models, function(index, model) {
                                $('#model_name').append('<option value="' + model.model_id + '">' + model.model_name + '</option>');
                            });
                        } else {
                            $('#model_name').append('<option value="">No models available</option>');
                        }
                    },
                    error: function() {
                        alert('Error fetching models.');
                    }
                });
            }
        });
    });
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

