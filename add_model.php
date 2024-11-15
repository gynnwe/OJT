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
            $duplicateCheckSQL = "SELECT COUNT(*) FROM model 
                                  WHERE model_name = :model_name 
                                  AND (model_id != :model_id OR :model_id IS NULL)
                                  AND deleted_id = 0";
            $stmt = $conn->prepare($duplicateCheckSQL);
            $stmt->bindParam(':model_name', $model_name);
            $stmt->bindParam(':model_id', $model_id);
            $stmt->execute();
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                $error = "Model name already exists. Please enter a unique model name.";
            } else {
                if ($model_id) {
                    $updateSQL = "UPDATE model SET model_name = :model_name, equip_type_id = :equip_type_id WHERE model_id = :model_id";
                    $stmt = $conn->prepare($updateSQL);
                    $stmt->bindParam(':model_name', $model_name);
                    $stmt->bindParam(':equip_type_id', $equip_type_id);
                    $stmt->bindParam(':model_id', $model_id);

                    if ($stmt->execute()) {
                        $_SESSION['message'] = "Model updated successfully.";
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
                    } else {
                        $error = "Failed to add the model.";
                    }
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

    $sql = "SELECT model.model_id, model.model_name, equipment_type.equip_type_name, model.equip_type_id
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
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 1500px;
        }
        .card {
            background-color: #ffffff;
            border-radius: 15px;
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            padding: 15px;
        }
        .add-edit-card {
            width: 800px;
            height: 166px;
            padding: 15px;
            position: relative;
        }
        .floating-alert {
            position: absolute;
            top: 0;
            right: 0;
            z-index: 1050;
            max-width: 400px;
            display: none;
            font-size: 0.7rem;
        }
        h1, h2 {
            font-weight: bold;
            color: #343a40;
            font-size: 1rem;
        }
        .section-divider {
            border: none;
            height: 1px;
            background-color: #ddd;
            margin: 10px 0;
        }
        .form-group {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 5px;
        }
        .form-group label {
            font-size: 0.9rem;
            width: 300px;
        }
        .form-control, #equip_type_id {
            border-radius: 30px;
            font-size: 0.8rem;
            padding: 5px 10px;
            border: 2px solid #646464;
            width: 300px;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.075);
        }
        .btn-save {
            background-color: #b32d2e;
            color: #fff;
            border: none;
            padding: 5px 15px;
            border-radius: 30px;
            font-size: 0.8rem;
            cursor: pointer;
            align-self: flex-end;
            margin-left: auto;
        }
        .btn-save:hover {
            background-color: #a02626;
        }
        #filterBy {
            background-color: #f1f1f1;
            color: #333;
            border: none;
            padding: 6px 10px;
            border-radius: 30px;
            width: 300px;
            font-size: 0.8rem;
        }
        .form-inline {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 20px;
        }
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
        table {
            width: 100%;
            background-color: #ffffff;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            font-size: 0.8rem;
        }
        th {
            background-color: #f1f1f1;
        }
        #searchInput {
            border-radius: 20px;
            font-size: 0.8rem;
            padding: 6px 10px;
            border: 2px solid #646464;
            width: 300px;
        }
        .pagination {
            justify-content: flex-end;
        }
        .pagination .page-link {
            border: none;
            font-size: 0.8rem;
            padding: 4px 8px;
        }
        .pagination .page-item:first-child .page-link {
            color: #8B8B8B;
        }
        .pagination .page-item:last-child .page-link {
            color: #474747;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card add-edit-card">
            <h1>Add Model for Equipment Type</h1>
            <hr class="section-divider">
            <?php if (isset($message)) echo "<div class='alert alert-success floating-alert' id='successAlert'>$message</div>"; ?>
            <?php if (isset($error)) echo "<div class='alert alert-danger floating-alert' id='errorAlert'>$error</div>"; ?>

            <form action="add_model.php" method="POST">
                <input type="hidden" name="model_id" id="model_id">

                <!-- Equipment Type Row -->
                <div class="form-group">
                    <label for="equip_type_id">Equipment Type:</label>
                    <select name="equip_type_id" id="equip_type_id" class="form-control" required>
                        <?php foreach ($equipment_types as $type): ?>
                            <option value="<?php echo $type['equip_type_id']; ?>"><?php echo htmlspecialchars($type['equip_type_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- New Model and Save Button Row -->
                <div class="form-group">
                    <label for="model_name">New Model for Chosen Equipment Type:</label>
                    <input type="text" name="model_name" id="model_name" class="form-control" required>
                    <button type="submit" class="btn-save">Save Model</button>
                </div>
            </form>
        </div>

        <div class="card search-card">
            <h2>List of Models</h2>
            <hr class="section-divider">
            <div class="form-inline">
                <select id="filterBy" class="mr-2">
                    <option value="id">ID</option>
                    <option value="type">Equipment Type</option>
                    <option value="name">Model Name</option>
                </select>
                <input type="text" id="searchInput" class="form-control" placeholder="Search...">
            </div>

            <h2>Existing Models</h2>
            <div class="table-responsive">
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
                        <?php foreach ($models as $model): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($model['model_id']); ?></td>
                                <td><?php echo htmlspecialchars($model['equip_type_name']); ?></td>
                                <td><?php echo htmlspecialchars($model['model_name']); ?></td>
                                <td>
                                    <a href="#" class="edit-btn" data-id="<?php echo $model['model_id']; ?>" data-name="<?php echo htmlspecialchars($model['model_name']); ?>" data-type="<?php echo $model['equip_type_id']; ?>">
                                        <img src="edit.png" alt="Edit" style="width: 20px;">
                                    </a>
                                    <a href="#" onclick="softDelete(<?php echo $model['model_id']; ?>)">
                                        <img src="delete.png" alt="Delete" style="width: 20px;">
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <nav>
                <ul class="pagination">
                    <li class="page-item"><a class="page-link" href="#">Previous</a></li>
                    <li class="page-item"><a class="page-link" href="#">Next</a></li>
                </ul>
            </nav>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            const successAlert = $('#successAlert');
            const errorAlert = $('#errorAlert');
            if (successAlert.length) {
                successAlert.fadeIn().delay(5000).fadeOut('slow', function() {
                    $(this).remove();
                });
            }
            if (errorAlert.length) {
                errorAlert.fadeIn().delay(5000).fadeOut('slow', function() {
                    $(this).remove();
                });
            }
        });

        $(document).on('click', '.edit-btn', function() {
            let modelId = $(this).data('id');
            let modelName = $(this).data('name');
            let equipTypeId = $(this).data('type');

            $('#model_id').val(modelId);
            $('#model_name').val(modelName);
            $('#equip_type_id').val(equipTypeId);
        });

        $('#searchInput').on('input', function() {
            let filter = $('#filterBy').val();
            let query = $(this).val().toLowerCase();

            $('#modelTableBody tr').each(function() {
                let text = filter === 'id'
                    ? $(this).find('td:first').text().toLowerCase()
                    : filter === 'type'
                    ? $(this).find('td:nth-child(2)').text().toLowerCase()
                    : $(this).find('td:nth-child(3)').text().toLowerCase();

                $(this).toggle(text.includes(query));
            });
        });

        function softDelete(id) {
            if (confirm('Are you sure you want to delete this model?')) {
                $.post('add_model.php', { deleted_id: id }, function(response) {
                    if (response.trim() === "Success") {
                        location.reload();
                    } else {
                        alert('Failed to delete the model.');
                    }
                });
            }
        }
    </script>
</body>
</html>
