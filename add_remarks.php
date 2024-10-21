<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ictmms";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remarks_name'])) {
        $remarks_name = trim($_POST['remarks_name']);
        $remarks_id = isset($_POST['remarks_id']) ? $_POST['remarks_id'] : null;

        if (!empty($remarks_name)) {
            $checkSQL = "SELECT COUNT(*) FROM remarks WHERE remarks_name = :remarks_name AND remarks_id != :remarks_id";
            $stmt = $conn->prepare($checkSQL);
            $stmt->bindValue(':remarks_name', $remarks_name);
            $stmt->bindValue(':remarks_id', $remarks_id ?? 0);
            $stmt->execute();
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                $error = "Remark already exists.";
            } else {
                if ($remarks_id) {
                    $updateSQL = "UPDATE remarks SET remarks_name = :remarks_name WHERE remarks_id = :remarks_id";
                    $stmt = $conn->prepare($updateSQL);
                    $stmt->bindValue(':remarks_name', $remarks_name);
                    $stmt->bindValue(':remarks_id', $remarks_id);
                    $stmt->execute();
                    $_SESSION['message'] = "Remark updated successfully.";
                } else {
                    $insertSQL = "INSERT INTO remarks (remarks_name) VALUES (:remarks_name)";
                    $stmt = $conn->prepare($insertSQL);
                    $stmt->bindValue(':remarks_name', $remarks_name);
                    $stmt->execute();
                    $_SESSION['message'] = "New remark added successfully.";
                }
                header("Location: add_remarks.php");
                exit;
            }
        } else {
            $error = "Remark cannot be empty.";
        }
    }

    if (isset($_POST['deleted_id'])) {
        $delete_id = $_POST['deleted_id'];
        $deleteSQL = "DELETE FROM remarks WHERE remarks_id = :remarks_id";
        $stmt = $conn->prepare($deleteSQL);
        $stmt->bindValue(':remarks_id', $delete_id);
        $stmt->execute();
        echo "Success";
        exit;
    }

    $sql = "SELECT remarks_id, remarks_name FROM remarks";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $remarks = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Add Remarks</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
</head>
<body>
    <div class="container mt-5">
        <h1>Add Remarks</h1>
        <?php if (isset($message)) echo "<div class='alert alert-success'>$message</div>"; ?>
        <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

        <form action="add_remarks.php" method="POST">
            <input type="hidden" name="remarks_id" id="remarks_id">
            <div class="form-group">
                <label for="remarks_name">New Remark:</label>
                <input type="text" name="remarks_name" id="remarks_name" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Add Remark</button>
        </form>

        <h2 class="mt-5">Filter Remarks</h2>
        <div class="form-inline mb-3">
            <select id="filterBy" class="form-control mr-2">
                <option value="id">ID</option>
                <option value="remarks_name">Remarks</option>
            </select>
            <input type="text" id="searchInput" class="form-control" placeholder="Search...">
        </div>

        <div id="noResultsMessage" class="alert alert-warning" style="display: none;">
            Remarks doesn't exist.
        </div>

        <h2>Existing Remarks</h2>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Remarks</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="remarksTableBody">
                <?php if (!empty($remarks)): ?>
                    <?php foreach ($remarks as $remark): ?>
                        <tr id="row-<?php echo htmlspecialchars($remark['remarks_id']); ?>">
                            <td><?php echo htmlspecialchars($remark['remarks_id']); ?></td>
                            <td><?php echo htmlspecialchars($remark['remarks_name']); ?></td>
                            <td>
                                <a href="#" onclick="editRemark(<?php echo htmlspecialchars($remark['remarks_id']); ?>)">
                                    <img src="edit.png" alt="Edit" style="width:20px; cursor: pointer;">
                                </a>
                                <a href="#" onclick="softDelete(<?php echo htmlspecialchars($remark['remarks_id']); ?>)">
                                    <img src="delete.png" alt="Delete" style="width:20px; cursor: pointer;">
                                </a>
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
        function editRemark(id) {
            const row = document.getElementById('row-' + id);
            const remarkName = row.cells[1].innerText;

            document.getElementById('remarks_id').value = id;
            document.getElementById('remarks_name').value = remarkName;
        }

        function softDelete(id) {
            if (confirm('Are you sure you want to delete this remark?')) {
                $.ajax({
                    url: 'add_remarks.php',
                    type: 'POST',
                    data: { deleted_id: id },
                    success: function (response) {
                        if (response.trim() === "Success") {
                            document.getElementById('row-' + id).style.display = 'none';
                        } else {
                            alert('Failed to delete the remark.');
                        }
                    }
                });
            }
        }

        // Filter and search functionality with no results prompt
        $('#searchInput').on('input', function () {
            const filterBy = $('#filterBy').val();
            const searchValue = $(this).val().toLowerCase();
            let found = false;

            $('#remarksTableBody tr').filter(function () {
                const match = $(this).find('td').eq(filterBy === 'id' ? 0 : 1).text().toLowerCase().includes(searchValue);
                $(this).toggle(match);
                if (match) found = true;
            });

            $('#noResultsMessage').toggle(!found);
        });
    </script>
</body>
</html>
