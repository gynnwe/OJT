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

    // Handle form submission to add new personnel
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['firstname'])) {
        $firstname = trim($_POST['firstname']);
        $lastname = trim($_POST['lastname']);
        $department = trim($_POST['department']);

        if (!empty($firstname) && !empty($lastname) && !empty($department)) {
            // Check if the personnel already exists
            $checkSQL = "SELECT COUNT(*) FROM personnel WHERE firstname = :firstname AND lastname = :lastname AND deleted_id = 0";
            $stmt = $conn->prepare($checkSQL);
            $stmt->bindParam(':firstname', $firstname);
            $stmt->bindParam(':lastname', $lastname);
            $stmt->execute();
            $count = $stmt->fetchColumn();

            if ($count == 0) {
                // Insert the new personnel into the database
                $insertSQL = "INSERT INTO personnel (firstname, lastname, department) VALUES (:firstname, :lastname, :department)";
                $stmt = $conn->prepare($insertSQL);
                $stmt->bindParam(':firstname', $firstname);
                $stmt->bindParam(':lastname', $lastname);
                $stmt->bindParam(':department', $department);

                if ($stmt->execute()) {
                    $_SESSION['message'] = "New personnel added successfully.";
                    // Redirect to avoid form resubmission on page refresh
                    header("Location: add_personnel.php");
                    exit;
                } else {
                    $error = "Failed to add the personnel.";
                }
            } else {
                $error = "Personnel already exists.";
            }
        } else {
            $error = "All fields are required.";
        }
    }

    // Handle soft delete via AJAX
    if (isset($_POST['deleted_id'])) {
        $delete_id = $_POST['deleted_id'];
        $softDeleteSQL = "UPDATE personnel SET deleted_id = 1 WHERE personnel_id = :deleted_id";
        $stmt = $conn->prepare($softDeleteSQL);
        $stmt->bindParam(':deleted_id', $delete_id);
        if ($stmt->execute()) {
            echo "Success";
        } else {
            echo "Failed to delete.";
        }
        exit;
    }

    // Fetch all non-deleted personnel for display purposes
    $sql = "SELECT personnel_id, firstname, lastname, department FROM personnel WHERE deleted_id = 0";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $personnel_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Add Personnel</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
</head>
<body>
    <div class="container mt-5">
        <h1>Add Personnel</h1>
        <?php
        if (isset($message)) {
            echo "<div class='alert alert-success'>$message</div>";
        }
        if (isset($error)) {
            echo "<div class='alert alert-danger'>$error</div>";
        }
        ?>
        <form action="add_personnel.php" method="POST">
            <div class="form-group">
                <label for="firstname">First Name:</label>
                <input type="text" name="firstname" id="firstname" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="lastname">Last Name:</label>
                <input type="text" name="lastname" id="lastname" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="department">Department:</label>
                <input type="text" name="department" id="department" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Add Personnel</button>
        </form>

        <h2 class="mt-5">Existing Personnel</h2>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Department</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($personnel_list)): ?>
                    <?php foreach ($personnel_list as $person): ?>
                        <tr id="row-<?php echo htmlspecialchars($person['personnel_id']); ?>">
                            <td><?php echo htmlspecialchars($person['personnel_id']); ?></td>
                            <td><?php echo htmlspecialchars($person['firstname']); ?></td>
                            <td><?php echo htmlspecialchars($person['lastname']); ?></td>
                            <td><?php echo htmlspecialchars($person['department']); ?></td>
                            <td>
                                <button class="btn btn-danger" onclick="softDelete(<?php echo htmlspecialchars($person['personnel_id']); ?>)">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">No personnel available.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        function softDelete(id) {
            if (confirm('Are you sure you want to delete this personnel?')) {
                $.ajax({
                    url: 'add_personnel.php',
                    type: 'POST',
                    data: { deleted_id: id },
                    success: function(response) {
                        if (response.trim() === "Success") {
                            document.getElementById('row-' + id).style.display = 'none';
                        } else {
                            alert('Failed to delete the personnel.');
                        }
                    }
                });
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>