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

    // Handle form submission to add a new model
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['equipment_type'], $_POST['model_name'])) {
        $equipment_type_id = $_POST['equipment_type'];
        $model_name = trim($_POST['model_name']);

        if (!empty($model_name)) {
            // Check if the model already exists for the selected equipment type
            $checkSQL = "SELECT COUNT(*) FROM model WHERE equip_type_id = :equip_type_id AND model_name = :model_name";
            $stmt = $conn->prepare($checkSQL);
            $stmt->bindParam(':equip_type_id', $equipment_type_id);
            $stmt->bindParam(':model_name', $model_name);
            $stmt->execute();
            $count = $stmt->fetchColumn();

            if ($count == 0) {
                // Insert the new model into the database
                $insertSQL = "INSERT INTO model (equip_type_id, model_name) VALUES (:equip_type_id, :model_name)";
                $stmt = $conn->prepare($insertSQL);
                $stmt->bindParam(':equip_type_id', $equipment_type_id);
                $stmt->bindParam(':model_name', $model_name);

                if ($stmt->execute()) {
                    $_SESSION['message'] = "New model added successfully.";
                    header("Location: add_model.php");
                    exit;
                } else {
                    $error = "Failed to add the model.";
                }
            } else {
                $error = "Model already exists for this equipment type.";
            }
        } else {
            $error = "Model name cannot be empty.";
        }
    }

    // Handles soft delete via AJAX
    if (isset($_POST['deleted_id'])) {
        $delete_id = $_POST['deleted_id'];
        $softDeleteSQL = "UPDATE model SET deleted_id = 1 WHERE model_id = :deleted_id";
        $stmt = $conn->prepare($softDeleteSQL);
        $stmt->bindParam(':deleted_id', $delete_id);
        if ($stmt->execute()) {
            echo "Success";
        } else {
            echo "Failed to delete.";
        }
        exit;
    }

	// Fetch all models along with their equipment types for display
	$sqlModels = "
		SELECT model.model_id, model.model_name, equipment_type.equip_type_name 
		FROM model 
		JOIN equipment_type ON model.equip_type_id = equipment_type.equip_type_id 
		WHERE equipment_type.deleted_id = 0 AND (model.deleted_id IS NULL OR model.deleted_id = 0)"; //filter out deleted models
    
    $stmtModels = $conn->prepare($sqlModels);
    $stmtModels->execute();
    $models = $stmtModels->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Model</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
	<div class="container mt-5">
        <h1>Add Model for Equipment Type</h1>
        <?php
        if (isset($message)) {
            echo "<div class='alert alert-success'>$message</div>";
        }
        if (isset($error)) {
            echo "<div class='alert alert-danger'>$error</div>";
        }
        ?>
		
		<form action="add_model.php" method="post">
            <div class="form-group">
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
                <label for="model_name">New Model for Chosen Equipment Type:</label>
                <input type="text" name="model_name" id="model_name" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Add Model</button>
        </form>
		
		<h2 class="mt-5">Existing Models</h2>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Equipment Type</th>
                    <th>Model Name</th>
                    <th>Actions</th> <!-- Added Actions column -->
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($models)): ?>
                    <?php foreach ($models as $model): ?>
                        <tr id="row-<?php echo htmlspecialchars($model['model_id']); ?>">
                            <td><?php echo htmlspecialchars($model['model_id']); ?></td>
                            <td><?php echo htmlspecialchars($model['equip_type_name']); ?></td>
                            <td><?php echo htmlspecialchars($model['model_name']); ?></td>
                            <td>
                                <button class="btn btn-danger" onclick="softDelete(<?php echo htmlspecialchars($model['model_id']); ?>)">Delete</button> <!-- Delete button -->
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">No Models available.</td> <!-- Adjusted colspan -->
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
	</div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script> <!-- Ensure jQuery is included -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function softDelete(id) {
            if (confirm('Are you sure you want to delete this model?')) {
                $.ajax({
                    url: 'add_model.php',
                    type: 'POST',
                    data: { deleted_id: id },
                    success: function(response) {
                        if (response.trim() === "Success") {
                            document.getElementById('row-' + id).style.display = 'none';
                        } else {
                            alert('Failed to delete the model.');
                        }
                    }
                });
            }
        }
    </script>

</body>
</html>