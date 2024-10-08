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

    // Handle form submission to add a new equipment type
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['equip_type_name'])) {
        $equip_type_name = trim($_POST['equip_type_name']);

        if (!empty($equip_type_name)) {
            // Check if the equipment type already exists
            $checkSQL = "SELECT COUNT(*) FROM equip_type WHERE equip_type_name = :equip_type_name AND deleted = 0";
            $stmt = $conn->prepare($checkSQL);
            $stmt->bindParam(':equip_type_name', $equip_type_name);
            $stmt->execute();
            $count = $stmt->fetchColumn();

            if ($count == 0) {
                // Insert the new equipment type into the database
                $insertSQL = "INSERT INTO equip_type (equip_type_name) VALUES (:equip_type_name)";
                $stmt = $conn->prepare($insertSQL);
                $stmt->bindParam(':equip_type_name', $equip_type_name);

                if ($stmt->execute()) {
                    $_SESSION['message'] = "New equipment type added successfully.";
                    // Redirect to avoid form resubmission on page refresh
                    header("Location: add_equipment_type.php");
                    exit;
                } else {
                    $error = "Failed to add the equipment type.";
                }
            } else {
                $error = "Equipment type already exists.";
            }
        } else {
            $error = "Equipment type name cannot be empty.";
        }
    }

    // Handle soft delete via AJAX
    if (isset($_POST['delete_id'])) {
        $delete_id = $_POST['delete_id'];
        $softDeleteSQL = "UPDATE equip_type SET deleted = 1 WHERE equip_type_id = :delete_id";
        $stmt = $conn->prepare($softDeleteSQL);
        $stmt->bindParam(':delete_id', $delete_id);
        $stmt->execute();
        echo "Success";
        exit;
    }

    // Fetch all non-deleted equipment types for display purposes
    $sql = "SELECT equip_type_id, equip_type_name FROM equip_type WHERE deleted = 0";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $equipment_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Display any messages and then clear them
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Equipment Type</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
</head>
<body>
    <div class="container mt-5">
        <h1>Add Equipment Type</h1>
        <?php
        if (isset($message)) {
            echo "<div class='alert alert-success'>$message</div>";
        }
        if (isset($error)) {
            echo "<div class='alert alert-danger'>$error</div>";
        }
        ?>
        <form action="add_equipment_type.php" method="post">
            <div class="form-group">
                <label for="equip_type_name">New Equipment Type:</label>
                <input type="text" name="equip_type_name" id="equip_type_name" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Add Equipment Type</button>
        </form>

        <h2 class="mt-5">Existing Equipment Types</h2>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Equipment Type Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($equipment_types)): ?>
                    <?php foreach ($equipment_types as $type): ?>
                        <tr id="row-<?php echo $type['equip_type_id']; ?>">
                            <td><?php echo htmlspecialchars($type['equip_type_id']); ?></td>
                            <td><?php echo htmlspecialchars($type['equip_type_name']); ?></td>
                            <td>
                                <button class="btn btn-danger" onclick="softDelete(<?php echo $type['equip_type_id']; ?>)">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3">No equipment types available.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        function softDelete(id) {
            if (confirm('Are you sure you want to delete this equipment type?')) {
                $.ajax({
                    url: 'add_equipment_type.php',
                    type: 'POST',
                    data: { delete_id: id },
                    success: function(response) {
                        if (response.trim() === "Success") {
                            document.getElementById('row-' + id).style.display = 'none';
                        } else {
                            alert('Failed to delete the equipment type.');
                        }
                    }
                });
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
