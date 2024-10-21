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

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['model_name'])) {
        $model_name = trim($_POST['model_name']);
        $equip_type_id = $_POST['equip_type_id'];
        $model_id = isset($_POST['model_id']) && !empty($_POST['model_id']) ? $_POST['model_id'] : null;

        if (!empty($model_name)) {
            if ($model_id) {
                $updateSQL = "UPDATE model SET model_name = :model_name, equip_type_id = :equip_type_id WHERE model_id = :model_id";
                $stmt = $conn->prepare($updateSQL);
                $stmt->bindParam(':model_name', $model_name);
                $stmt->bindParam(':equip_type_id', $equip_type_id);
                $stmt->bindParam(':model_id', $model_id);

                if ($stmt->execute()) {
                    $_SESSION['message'] = "Model updated successfully.";
                    header("Location: add_model.php");
                    exit;
                } else {
                    $error = "Failed to update the model.";
                }
            } else {
                $insertSQL = "INSERT INTO model (model_name, equip_type_id) VALUES (:model_name, :equip_type_id)";
                $stmt = $conn->prepare($insertSQL);
                $stmt->bindParam(':model_name', $model_name);
                $stmt->bindParam(':equip_type_id', $equip_type_id);

                if ($stmt->execute()) {
                    $_SESSION['message'] = "New model added successfully.";
                    header("Location: add_model.php");
                    exit;
                } else {
                    $error = "Failed to add the model.";
                }
            }
        } else {
            $error = "Model name cannot be empty.";
        }
    }

    if (isset($_POST['deleted_id'])) {
        $delete_id = $_POST['deleted_id'];
        $softDeleteSQL = "UPDATE model SET deleted_id = 1 WHERE model_id = :deleted_id";
        $stmt = $conn->prepare($softDeleteSQL);
        $stmt->bindParam(':deleted_id', $delete_id);
        $stmt->execute();
        echo "Success";
        exit;
    }

    $sql = "SELECT model.model_id, model.model_name, equipment_type.equip_type_name 
            FROM model 
            JOIN equipment_type ON model.equip_type_id = equipment_type.equip_type_id 
            WHERE model.deleted_id = 0";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $models = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $equipSQL = "SELECT equip_type_id, equip_type_name FROM equipment_type WHERE deleted_id = 0";
    $stmt = $conn->prepare($equipSQL);
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
    <title>Add Model for Equipment Type</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
</head>
<body>
    <div class="container mt-5">
        <h1>Add Model for Equipment Type</h1>
        <?php if (isset($message)) echo "<div class='alert alert-success'>$message</div>"; ?>
        <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

        <form action="add_model.php" method="POST">
            <input type="hidden" name="model_id" id="model_id">
            <div class="form-group">
                <label for="equip_type_id">Equipment Type:</label>
                <select name="equip_type_id" id="equip_type_id" class="form-control" required>
                    <?php foreach ($equipment_types as $type): ?>
                        <option value="<?php echo $type['equip_type_id']; ?>"><?php echo htmlspecialchars($type['equip_type_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="model_name">New Model for Chosen Equipment Type:</label>
                <input type="text" name="model_name" id="model_name" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Save Model</button>
        </form>

        <h2 class="mt-5">Filter Models</h2>
        <div class="form-inline mb-3">
            <select id="filterBy" class="form-control mr-2">
                <option value="id">ID</option>
                <option value="type">Equipment Type</option>
                <option value="name">Model Name</option>
            </select>
            <input type="text" id="searchInput" class="form-control" placeholder="Search...">
        </div>

        <h2>Existing Models</h2>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Equipment Type</th>
                    <th>Model Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="modelTableBody">
                <?php if (!empty($models)): ?>
                    <?php foreach ($models as $model): ?>
                        <tr id="row-<?php echo htmlspecialchars($model['model_id']); ?>">
                            <td><?php echo htmlspecialchars($model['model_id']); ?></td>
                            <td><?php echo htmlspecialchars($model['equip_type_name']); ?></td>
                            <td><?php echo htmlspecialchars($model['model_name']); ?></td>
                            <td>
                                <button class="btn btn-danger" onclick="softDelete(<?php echo htmlspecialchars($model['model_id']); ?>)">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">No Models available.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        $('#searchInput').on('input', function () {
            let filter = $('#filterBy').val();
            let query = $(this).val().trim().toLowerCase();
            let matchFound = false;

            $('#modelTableBody tr').each(function () {
                let text = filter === 'id'
                    ? $(this).find('td:first').text().trim().toLowerCase()
                    : filter === 'type'
                    ? $(this).find('td:nth-child(2)').text().trim().toLowerCase()
                    : $(this).find('td:nth-child(3)').text().trim().toLowerCase();

                if (text.includes(query)) {
                    $(this).show();
                    matchFound = true;
                } else {
                    $(this).hide();
                }
            });

            if (!matchFound && query !== '') {
                alert('Model doesn\'t exist');
            }
        });

        function softDelete(id) {
            if (confirm('Are you sure you want to delete this model?')) {
                $.ajax({
                    url: 'add_model.php',
                    type: 'POST',
                    data: { deleted_id: id },
                    success: function (response) {
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
