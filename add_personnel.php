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

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['firstname'])) {
        $firstname = trim($_POST['firstname']);
        $lastname = trim($_POST['lastname']);
        $department = trim($_POST['department']);
        $personnel_id = isset($_POST['personnel_id']) ? $_POST['personnel_id'] : null;

        if (!empty($firstname) && !empty($lastname) && !empty($department)) {
            // Check for duplicate personnel, ignoring soft-deleted ones
            $checkSQL = "SELECT COUNT(*) FROM personnel 
                         WHERE firstname = :firstname AND lastname = :lastname 
                         AND personnel_id != :personnel_id AND deleted_id = 0";
            $stmt = $conn->prepare($checkSQL);
            $stmt->bindValue(':firstname', $firstname);
            $stmt->bindValue(':lastname', $lastname);
            $stmt->bindValue(':personnel_id', $personnel_id ?? 0);
            $stmt->execute();
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                $error = "Personnel with the same name already exists.";
            } else {
                if ($personnel_id) {
                    // Update existing personnel
                    $updateSQL = "UPDATE personnel SET firstname = :firstname, lastname = :lastname, 
                                  department = :department WHERE personnel_id = :personnel_id";
                    $stmt = $conn->prepare($updateSQL);
                    $stmt->bindValue(':firstname', $firstname);
                    $stmt->bindValue(':lastname', $lastname);
                    $stmt->bindValue(':department', $department);
                    $stmt->bindValue(':personnel_id', $personnel_id);
                    $stmt->execute();
                    $_SESSION['message'] = "Personnel updated successfully.";
                } else {
                    // Insert new personnel
                    $insertSQL = "INSERT INTO personnel (firstname, lastname, department) 
                                  VALUES (:firstname, :lastname, :department)";
                    $stmt = $conn->prepare($insertSQL);
                    $stmt->bindValue(':firstname', $firstname);
                    $stmt->bindValue(':lastname', $lastname);
                    $stmt->bindValue(':department', $department);
                    $stmt->execute();
                    $_SESSION['message'] = "New personnel added successfully.";
                }
                header("Location: add_personnel.php");
                exit;
            }
        } else {
            $error = "All fields are required.";
        }
    }

    if (isset($_POST['deleted_id'])) {
        $delete_id = $_POST['deleted_id'];
        // Soft delete by setting deleted_id to 1
        $softDeleteSQL = "UPDATE personnel SET deleted_id = 1 WHERE personnel_id = :personnel_id";
        $stmt = $conn->prepare($softDeleteSQL);
        $stmt->bindValue(':personnel_id', $delete_id);
        $stmt->execute();
        echo "Success";
        exit;
    }

    // Fetch only non-deleted personnel
    $sql = "SELECT * FROM personnel WHERE deleted_id = 0";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $personnel = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Add Personnel</title>
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
            width: 600px;
            height: auto;
            padding: 15px;
            position: relative;
        }
        .floating-alert {
            position: absolute;
            top: 0;
            right: 0;
            z-index: 1050;
            max-width: 300px;
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
            width: 100px;
        }
        .form-control {
            border-radius: 30px;
            font-size: 0.8rem;
            padding: 10px;
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
            <h1>Add Personnel</h1>
            <hr class="section-divider">
            <?php if (isset($message)) echo "<div class='alert alert-success floating-alert' id='successAlert'>$message</div>"; ?>
            <?php if (isset($error)) echo "<div class='alert alert-danger floating-alert' id='errorAlert'>$error</div>"; ?>

            <form action="add_personnel.php" method="POST">
                <input type="hidden" name="personnel_id" id="personnel_id">

                <!-- First Name Field -->
                <div class="form-group">
                    <label for="firstname">First Name:</label>
                    <input type="text" name="firstname" id="firstname" class="form-control" required>
                </div>

                <!-- Last Name Field -->
                <div class="form-group">
                    <label for="lastname">Last Name:</label>
                    <input type="text" name="lastname" id="lastname" class="form-control" required>
                </div>

                <!-- Department Field with Button Next to It -->
                <div class="form-group">
                    <label for="department">Department:</label>
                    <input type="text" name="department" id="department" class="form-control" required>
                    <button type="submit" class="btn-save">Add Personnel</button>
                </div>
            </form>
        </div>

        <div class="card search-card">
            <h2>List of Personnel</h2>
            <hr class="section-divider">
            <div class="form-inline">
                <select id="filterBy" class="form-control mr-2">
                    <option value="id">ID</option>
                    <option value="firstname">First Name</option>
                    <option value="lastname">Last Name</option>
                    <option value="department">Department</option>
                </select>
                <input type="text" id="searchInput" class="form-control" placeholder="Search...">
            </div>

            <h2>Existing Personnel</h2>
            <div class="table-responsive">
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
                    <tbody id="personnelTableBody">
                        <?php if (!empty($personnel)): ?>
                            <?php foreach ($personnel as $person): ?>
                                <tr id="row-<?php echo htmlspecialchars($person['personnel_id']); ?>">
                                    <td><?php echo htmlspecialchars($person['personnel_id']); ?></td>
                                    <td><?php echo htmlspecialchars($person['firstname']); ?></td>
                                    <td><?php echo htmlspecialchars($person['lastname']); ?></td>
                                    <td><?php echo htmlspecialchars($person['department']); ?></td>
                                    <td>
                                        <a href="#" onclick="editPersonnel(<?php echo htmlspecialchars($person['personnel_id']); ?>)">
                                            <img src="edit.png" alt="Edit" style="width:20px; cursor: pointer;">
                                        </a>
                                        <a href="#" onclick="softDelete(<?php echo htmlspecialchars($person['personnel_id']); ?>)">
                                            <img src="delete.png" alt="Delete" style="width:20px; cursor: pointer;">
                                        </a>
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
            
            $('#searchInput').on('keyup', function() {
                const filterBy = $('#filterBy').val();
                const value = $(this).val().toLowerCase();
                let isMatch = false;

                $('#personnelTableBody tr').filter(function() {
                    const match = $(this).find(`td:nth-child(${filterBy === 'id' ? 1 : filterBy === 'firstname' ? 2 : filterBy === 'lastname' ? 3 : 4})`)
                        .text().toLowerCase().indexOf(value) > -1;
                    $(this).toggle(match);
                    if (match) isMatch = true;
                });

                if (!isMatch && value !== '') {
                    alert("Personnel doesn't exist.");
                }
            });
        });

        function editPersonnel(id) {
            const row = document.getElementById('row-' + id);
            const firstname = row.cells[1].innerText;
            const lastname = row.cells[2].innerText;
            const department = row.cells[3].innerText;

            document.getElementById('personnel_id').value = id;
            document.getElementById('firstname').value = firstname;
            document.getElementById('lastname').value = lastname;
            document.getElementById('department').value = department;
        }

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
</body>
</html>
