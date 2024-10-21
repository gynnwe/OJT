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

    // Handle form submission to add or edit equipment types
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['equip_type_name'])) {
        $equip_type_name = trim($_POST['equip_type_name']);
        $equip_type_id = isset($_POST['equip_type_id']) ? $_POST['equip_type_id'] : null;

        if (!empty($equip_type_name)) {
            $checkSQL = "SELECT COUNT(*) FROM equipment_type 
                         WHERE equip_type_name = :equip_type_name 
                         AND deleted_id = 0 
                         AND (:equip_type_id IS NULL OR equip_type_id != :equip_type_id)";
            $stmt = $conn->prepare($checkSQL);
            $stmt->bindParam(':equip_type_name', $equip_type_name);
            $stmt->bindParam(':equip_type_id', $equip_type_id);
            $stmt->execute();
            $count = $stmt->fetchColumn();

            if ($count == 0) {
                if ($equip_type_id) {
                    $updateSQL = "UPDATE equipment_type 
                                  SET equip_type_name = :equip_type_name 
                                  WHERE equip_type_id = :equip_type_id";
                    $stmt = $conn->prepare($updateSQL);
                    $stmt->bindParam(':equip_type_name', $equip_type_name);
                    $stmt->bindParam(':equip_type_id', $equip_type_id);
                } else {
                    $insertSQL = "INSERT INTO equipment_type (equip_type_name) 
                                  VALUES (:equip_type_name)";
                    $stmt = $conn->prepare($insertSQL);
                    $stmt->bindParam(':equip_type_name', $equip_type_name);
                }

                if ($stmt->execute()) {
                    $_SESSION['message'] = $equip_type_id ? "Equipment type updated successfully." : "New equipment type added successfully.";
                    header("Location: add_equipment_type.php");
                    exit;
                } else {
                    $error = "Failed to save the equipment type.";
                }
            } else {
                $error = "Equipment type already exists.";
            }
        } else {
            $error = "Equipment type name cannot be empty.";
        }
    }

    if (isset($_POST['deleted_id'])) {
        $delete_id = $_POST['deleted_id'];
        $softDeleteSQL = "UPDATE equipment_type SET deleted_id = 1 WHERE equip_type_id = :deleted_id";
        $stmt = $conn->prepare($softDeleteSQL);
        $stmt->bindParam(':deleted_id', $delete_id);
        $stmt->execute();
        echo "Success";
        exit;
    }

    $sql = "SELECT equip_type_id, equip_type_name FROM equipment_type WHERE deleted_id = 0";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $equipment_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Add Equipment Type</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
</head>
<body>
    <div class="container mt-5">
        <h1>Add/Edit Equipment Type</h1>
        <?php
        if (isset($message)) {
            echo "<div class='alert alert-success'>$message</div>";
        }
        if (isset($error)) {
            echo "<div class='alert alert-danger'>$error</div>";
        }
        ?>
        <form action="add_equipment_type.php" method="POST">
            <input type="hidden" name="equip_type_id" id="equip_type_id">
            <div class="form-group">
                <label for="equip_type_name">Equipment Type Name:</label>
                <input type="text" name="equip_type_name" id="equip_type_name" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Save Equipment Type</button>
        </form>

        <h2 class="mt-5">Search Equipment Type</h2>
        <div class="form-inline mb-3">
            <select id="filterBy" class="form-control mr-2">
                <option value="id">ID</option>
                <option value="name">Equipment Type Name</option>
            </select>
            <input type="text" id="searchInput" class="form-control" placeholder="Search...">
        </div>

        <h2>Existing Equipment Types</h2>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Equipment Type Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="equipmentTableBody">
                <?php if (!empty($equipment_types)): ?>
                    <?php foreach ($equipment_types as $type): ?>
                        <tr id="row-<?php echo $type['equip_type_id']; ?>">
                            <td><?php echo htmlspecialchars($type['equip_type_id']); ?></td>
                            <td><?php echo htmlspecialchars($type['equip_type_name']); ?></td>
                            <td>
                                <a href="#" onclick="editEquipment(<?php echo $type['equip_type_id']; ?>, '<?php echo $type['equip_type_name']; ?>')">
                                    <img src="edit.png" alt="Edit" style="width:20px; cursor: pointer;">
                                </a>
                                <a href="#" onclick="softDelete(<?php echo $type['equip_type_id']; ?>)">
                                    <img src="delete.png" alt="Delete" style="width:20px; cursor: pointer;">
                                </a>
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
        function editEquipment(id, name) {
            document.getElementById('equip_type_id').value = id;
            document.getElementById('equip_type_name').value = name;
        }

        function softDelete(id) {
            if (confirm('Are you sure you want to delete this equipment type?')) {
                $.ajax({
                    url: 'add_equipment_type.php',
                    type: 'POST',
                    data: { deleted_id: id },
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

        $('#searchInput').on('input', function() {
            let filter = $('#filterBy').val();
            let query = $(this).val().toLowerCase();
            let found = false;

            $('#equipmentTableBody tr').each(function() {
                let text = filter === 'id' 
                    ? $(this).find('td:first').text() 
                    : $(this).find('td:nth-child(2)').text().toLowerCase();

                if (text.includes(query)) {
                    $(this).show();
                    found = true;
                } else {
                    $(this).hide();
                }
            });

            if (!found) alert('Equipment doesn\'t exist');
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
