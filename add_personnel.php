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
            $checkSQL = "SELECT COUNT(*) FROM personnel 
                         WHERE firstname = :firstname AND lastname = :lastname 
                         AND personnel_id != :personnel_id";
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
        $deleteSQL = "DELETE FROM personnel WHERE personnel_id = :personnel_id";
        $stmt = $conn->prepare($deleteSQL);
        $stmt->bindValue(':personnel_id', $delete_id);
        $stmt->execute();
        echo "Success";
        exit;
    }

    $sql = "SELECT * FROM personnel";
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
</head>
<body>
    <div class="container mt-5">
        <h1>Add Personnel</h1>
        <?php if (isset($message)) echo "<div class='alert alert-success'>$message</div>"; ?>
        <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

        <form action="add_personnel.php" method="POST">
            <input type="hidden" name="personnel_id" id="personnel_id">
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

        <h2 class="mt-5">Filter Personnel</h2>
        <div class="form-inline mb-3">
            <select id="filterBy" class="form-control mr-2">
                <option value="id">ID</option>
                <option value="firstname">First Name</option>
                <option value="lastname">Last Name</option>
                <option value="department">Department</option>
            </select>
            <input type="text" id="searchInput" class="form-control" placeholder="Search...">
        </div>

        <h2>Existing Personnel</h2>
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

    <script>
        $(document).ready(function() {
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

                if (!isMatch) {
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
