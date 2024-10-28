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
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['college'])) {
        $college = trim($_POST['college']);
        $office = trim($_POST['office']);
        $unit = trim($_POST['unit']);
        $location_id = isset($_POST['location_id']) ? $_POST['location_id'] : null;

        if (!empty($college) && !empty($office) && !empty($unit)) {
            // Check for duplicate entries, ignoring soft-deleted locations
            $checkSQL = "SELECT COUNT(*) FROM location 
                         WHERE college = :college AND office = :office AND unit = :unit 
                         AND location_id != :location_id AND deleted_id = 0";
            $stmt = $conn->prepare($checkSQL);
            $stmt->bindValue(':college', $college);
            $stmt->bindValue(':office', $office);
            $stmt->bindValue(':unit', $unit);
            $stmt->bindValue(':location_id', $location_id ?? 0);
            $stmt->execute();
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                $error = "Location already exists.";
            } else {
                if ($location_id) {
                    // Update existing location
                    $updateSQL = "UPDATE location SET college = :college, office = :office, unit = :unit 
                                  WHERE location_id = :location_id";
                    $stmt = $conn->prepare($updateSQL);
                    $stmt->bindValue(':college', $college);
                    $stmt->bindValue(':office', $office);
                    $stmt->bindValue(':unit', $unit);
                    $stmt->bindValue(':location_id', $location_id);
                    $stmt->execute();
                    $_SESSION['message'] = "Location updated successfully.";
                } else {
                    // Insert new location
                    $insertSQL = "INSERT INTO location (college, office, unit) 
                                  VALUES (:college, :office, :unit)";
                    $stmt = $conn->prepare($insertSQL);
                    $stmt->bindValue(':college', $college);
                    $stmt->bindValue(':office', $office);
                    $stmt->bindValue(':unit', $unit);
                    $stmt->execute();
                    $_SESSION['message'] = "New location added successfully.";
                }
                header("Location: add_location.php");
                exit;
            }
        } else {
            $error = "All fields are required.";
        }
    }

    if (isset($_POST['deleted_id'])) {
        $delete_id = $_POST['deleted_id'];
        // Soft delete instead of actual delete
        $softDeleteSQL = "UPDATE location SET deleted_id = 1 WHERE location_id = :location_id";
        $stmt = $conn->prepare($softDeleteSQL);
        $stmt->bindValue(':location_id', $delete_id);
        $stmt->execute();
        echo "Success";
        exit;
    }

    // Fetch only non-deleted locations
    $sql = "SELECT * FROM location WHERE deleted_id = 0";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

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
        <?php if (isset($message)) echo "<div class='alert alert-success'>$message</div>"; ?>
        <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

        <form action="add_location.php" method="POST">
            <input type="hidden" name="location_id" id="location_id">
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

        <h2 class="mt-5">Filter Locations</h2>
        <div class="form-inline mb-3">
            <select id="filterBy" class="form-control mr-2">
                <option value="id">ID</option>
                <option value="college">College</option>
                <option value="office">Office</option>
                <option value="unit">Unit</option>
            </select>
            <input type="text" id="searchInput" class="form-control" placeholder="Search...">
        </div>

        <h2>Existing Locations</h2>
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
            <tbody id="locationTableBody">
                <?php foreach ($locations as $location): ?>
                    <tr id="row-<?php echo htmlspecialchars($location['location_id']); ?>">
                        <td><?php echo htmlspecialchars($location['location_id']); ?></td>
                        <td><?php echo htmlspecialchars($location['college']); ?></td>
                        <td><?php echo htmlspecialchars($location['office']); ?></td>
                        <td><?php echo htmlspecialchars($location['unit']); ?></td>
                        <td>
                            <a href="#" onclick="editLocation(<?php echo htmlspecialchars($location['location_id']); ?>)">
                                <img src="edit.png" alt="Edit" style="width:20px; cursor: pointer;">
                            </a>
                            <a href="#" onclick="softDelete(<?php echo htmlspecialchars($location['location_id']); ?>)">
                                <img src="delete.png" alt="Delete" style="width:20px; cursor: pointer;">
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        function editLocation(id) {
            const row = document.getElementById('row-' + id);
            const college = row.cells[1].innerText;
            const office = row.cells[2].innerText;
            const unit = row.cells[3].innerText;

            document.getElementById('location_id').value = id;
            document.getElementById('college').value = college;
            document.getElementById('office').value = office;
            document.getElementById('unit').value = unit;
        }

        function softDelete(id) {
            if (confirm('Are you sure you want to delete this location?')) {
                $.post('add_location.php', { deleted_id: id }, function(response) {
                    if (response.trim() === "Success") {
                        document.getElementById('row-' + id).style.display = 'none';
                    } else {
                        alert('Failed to delete the location.');
                    }
                });
            }
        }

        $('#searchInput').on('input', function () {
            let filter = $('#filterBy').val();
            let query = $(this).val().trim().toLowerCase();
            let matchFound = false;

            $('#locationTableBody tr').each(function () {
                let text = $(this).find(`td:nth-child(${filter === 'id' ? 1 : filter === 'college' ? 2 : filter === 'office' ? 3 : 4})`).text().trim().toLowerCase();
                if (text.includes(query)) {
                    $(this).show();
                    matchFound = true;
                } else {
                    $(this).hide();
                }
            });

            if (!matchFound && query !== '') {
                alert('Location not found.');
            }
        });
    </script>
</body>
</html>
