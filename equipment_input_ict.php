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
    $sql = "SELECT equip_type_id, equip_type_name FROM equip_type WHERE deleted = 0";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $equipment_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all locations from the database (if needed)
    $sql = "SELECT location_id, college, office, unit FROM location";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
            <label for="location_id">Location ID:</label>
            <input type="text" name="location_id" id="location_id" required><br>

            <label for="equipment_type">Equipment Type:</label>
            <select name="equipment_type" id="equipment_type" required>
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

            <label for="equipment_name">Equipment Name:</label>
            <input type="text" name="equipment_name" id="equipment_name" required><br>

            <label for="serial_num">Equipment Serial Number:</label>
            <input type="text" name="serial_num" id="serial_num" required><br>

            <label for="model_name">Model Name:</label>
            <input type="text" name="model_name" id="model_name" required><br>

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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
