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

    // Ensure the 'deleted' column exists
    $alterTableSQL = "
    ALTER TABLE location
    ADD COLUMN IF NOT EXISTS deleted TINYINT(1) NOT NULL DEFAULT 0";
    $conn->exec($alterTableSQL);

    // Handle form submission to add a new location
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['college'], $_POST['office'], $_POST['unit'])) {
        $college = trim($_POST['college']);
        $office = trim($_POST['office']);
        $unit = trim($_POST['unit']);

        if (!empty($college) && !empty($office) && !empty($unit)) {
            // Insert the new location into the database
            $insertSQL = "INSERT INTO location (college, office, unit) VALUES (:college, :office, :unit)";
            $stmt = $conn->prepare($insertSQL);
            $stmt->bindParam(':college', $college);
            $stmt->bindParam(':office', $office);
            $stmt->bindParam(':unit', $unit);

            if ($stmt->execute()) {
                $_SESSION['message'] = "New location added successfully.";
                // Redirect to avoid form resubmission
                header("Location: add_location.php");
                exit;
            } else {
                $error = "Failed to add the location.";
            }
        } else {
            $error = "All fields are required.";
        }
    }

    // Handle soft delete via AJAX
    if (isset($_POST['delete_id'])) {
        $delete_id = $_POST['delete_id'];
        $softDeleteSQL = "UPDATE location SET deleted = 1 WHERE location_id = :delete_id";
        $stmt = $conn->prepare($softDeleteSQL);
        $stmt->bindParam(':delete_id', $delete_id);
        $stmt->execute();
        echo "Success";
        exit;
    }

    // Fetch all non-deleted locations for display
    $sql = "SELECT * FROM location WHERE deleted = 0";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Display session messages and clear them
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
    <title>Add Location</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
</head>
<body>
    <div class="container mt-5">
        <h1>Add Location</h1>
        <?php
        if (isset($message)) {
            echo "<div class='alert alert-success'>$message</div>";
        }
        if (isset($error)) {
            echo "<div class='alert alert-danger'>$error</div>";
        }
        ?>
        <form action="add_location.php" method="post">
            <div class="form-group">
                <label for="college">College:</label>
                <input type="text" name="college" id="college" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="office">Office:</label>
                <input type="text" name="office" id="office" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="unit">Unit:</label>
                <input type="text" name="unit" id="unit" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Add Location</button>
        </form>

        <h2 class="mt-5">Existing Locations</h2>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>College</th>
                    <th>Office</th>
                    <th>Unit</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($locations)): ?>
                    <?php foreach ($locations as $location): ?>
                        <tr id="row-<?php echo $location['location_id']; ?>">
                            <td><?php echo htmlspecialchars($location['location_id']); ?></td>
                            <td><?php echo htmlspecialchars($location['college']); ?></td>
                            <td><?php echo htmlspecialchars($location['office']); ?></td>
                            <td><?php echo htmlspecialchars($location['unit']); ?></td>
                            <td>
                                <button class="btn btn-danger" onclick="softDelete(<?php echo $location['location_id']; ?>)">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">No locations available.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
    function softDelete(id) {
        if (confirm('Are you sure you want to delete this location?')) {
            $.ajax({
                url: 'add_location.php', 
                type: 'POST',
                data: { delete_id: id },
                success: function(response) {
                    if (response.trim() === "Success") {
                        document.getElementById('row-' + id).style.display = 'none';
                    } else {
                        alert('Failed to delete the location.');
                    }
                }
            });
        }
    }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
