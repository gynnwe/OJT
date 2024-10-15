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
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remarks_name'])) {
        $remarks_name = trim($_POST['remarks_name']);

        if (!empty($remarks_name)) {
            // Check if the equipment type already exists
            $checkSQL = "SELECT COUNT(*) FROM remarks WHERE remarks_name = :remarks_name AND deleted_id = 0";
            $stmt = $conn->prepare($checkSQL);
            $stmt->bindParam(':remarks_name', $remarks_name);
            $stmt->execute();
            $count = $stmt->fetchColumn();

            if ($count == 0) {
                // Insert the new equipment type into the database
                $insertSQL = "INSERT INTO remarks (remarks_name) VALUES (:remarks_name)";
                $stmt = $conn->prepare($insertSQL);
                $stmt->bindParam(':remarks_name', $remarks_name);

                if ($stmt->execute()) {
                    $_SESSION['message'] = "New remark added successfully.";
                    // Redirect to avoid form resubmission on page refresh
                    header("Location: add_remarks.php");
                    exit;
                } else {
                    $error = "Failed to add the remark.";
                }
            } else {
                $error = "Remark already exists.";
            }
        } else {
            $error = "Remark cannot be empty.";
        }
    }

    // Handle soft delete via AJAX
    if (isset($_POST['deleted_id'])) {
        $delete_id = $_POST['deleted_id'];
		$softDeleteSQL = "UPDATE remarks SET deleted_id = 1 WHERE remarks_id = :deleted_id";
        $stmt = $conn->prepare($softDeleteSQL);
        $stmt->bindParam(':deleted_id', $delete_id);
        $stmt->execute();
        echo "Success";
        exit;
    }

    // Fetch all non-deleted remarks for display purposes
    $sql = "SELECT remarks_id, remarks_name FROM remarks WHERE deleted_id = 0";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $remarks = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        <h1>Add Remarks</h1>
        <?php
        if (isset($message)) {
            echo "<div class='alert alert-success'>$message</div>";
        }
        if (isset($error)) {
            echo "<div class='alert alert-danger'>$error</div>";
        }
        ?>
        <form action="add_remarks.php" method="POST">
            <div class="form-group">
                <label for="remarks_name">New Remark:</label>
                <input type="text" name="remarks_name" id="remarks_name" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Add Remark</button>
        </form>

        <h2 class="mt-5">Existing remarks_name</h2>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Remarks</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($remarks)): ?>
                    <?php foreach ($remarks as $type): ?>
                        <tr id="row-<?php echo $type['remarks_id']; ?>">
                            <td><?php echo htmlspecialchars($type['remarks_id']); ?></td>
                            <td><?php echo htmlspecialchars($type['remarks_name']); ?></td>
                            <td>
                                <button class="btn btn-danger" onclick="softDelete(<?php echo $type['remarks_id']; ?>)">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3">No remarks added.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        function softDelete(id) {
            if (confirm('Are you sure you want to delete this remark')) {
                $.ajax({
                    url: 'add_remarks.php',
                    type: 'POST',
                    data: { deleted_id: id },
                    success: function(response) {
                        if (response.trim() === "Success") {
                            document.getElementById('row-' + id).style.display = 'none';
                        } else {
                            alert('Failed to delete the remark.');
                        }
                    }
                });
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
