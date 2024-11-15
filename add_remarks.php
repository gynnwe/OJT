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
            // Check for duplicate remarks, ignoring soft-deleted ones
            $checkSQL = "SELECT COUNT(*) FROM remarks 
                         WHERE remarks_name = :remarks_name 
                         AND remarks_id != :remarks_id 
                         AND deleted_id = 0";
            $stmt = $conn->prepare($checkSQL);
            $stmt->bindValue(':remarks_name', $remarks_name);
            $stmt->bindValue(':remarks_id', $remarks_id ?? 0);
            $stmt->execute();
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                $error = "Remark already exists.";
            } else {
                if ($remarks_id) {
                    // Update existing remark
                    $updateSQL = "UPDATE remarks SET remarks_name = :remarks_name WHERE remarks_id = :remarks_id";
                    $stmt = $conn->prepare($updateSQL);
                    $stmt->bindValue(':remarks_name', $remarks_name);
                    $stmt->bindValue(':remarks_id', $remarks_id);
                    $stmt->execute();
                    $_SESSION['message'] = "Remark updated successfully.";
                } else {
                    // Insert new remark
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
        // Soft delete by updating deleted_id
        $softDeleteSQL = "UPDATE remarks SET deleted_id = 1 WHERE remarks_id = :remarks_id";
        $stmt = $conn->prepare($softDeleteSQL);
        $stmt->bindValue(':remarks_id', $delete_id);
        $stmt->execute();
        echo "Success";
        exit;
    }

    // Fetch only non-deleted remarks
    $sql = "SELECT remarks_id, remarks_name FROM remarks WHERE deleted_id = 0";
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
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 1500px;
            display: flex;
            flex-wrap: wrap;
        }
        .card {
            background-color: #ffffff;
            padding: 15px;
            border-radius: 15px;
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .add-edit-card, .search-card {
            width: 1380px;
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
        .form-group label {
            font-size: 0.9rem;
            display: block;
        }
        .btn-save {
            background-color: #b32d2e;
            color: #fff;
            border: none;
            padding: 5px 30px;
            border-radius: 30px;
            font-size: 0.8rem;
            cursor: pointer;
            align-self: flex-end;
            margin-left: auto;
        }
        .btn-save:hover {
            background-color: #a02626;
        }
        .form-control {
            border-radius: 30px;
            font-size: 0.8rem;
            padding: 10px;
            border: 2px solid #646464;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.075);
            width: 100%;
        }
        #filterBy {
            width: 300px;
            background-color: #f1f1f1;
            color: #333;
            font-size: 0.8rem;
            border: none;
            padding: 6px 10px;
            border-radius: 30px;
        }
        .form-container {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .form-group {
            flex: 1;
            margin-bottom: 0;
        }
        .btn-save {
            margin-top: 25px;
        }
        table {
            width: 100%;
            background-color: #ffffff;
            border-collapse: collapse;
            margin-top: 10px;
            border-radius: 10px;
            overflow: hidden;
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
            width: 300px;
            margin-right: 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            padding: 6px 10px;
            border: 2px solid #646464;
        }
        img {
            transition: transform 0.2s;
        }
        img:hover {
            transform: scale(1.2);
        }
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
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
            <h1>Add/Edit Remark</h1>
            <hr class="section-divider">
            <?php if (isset($message)) echo "<div class='alert alert-success floating-alert' id='successAlert'>$message</div>"; ?>
            <?php if (isset($error)) echo "<div class='alert alert-danger floating-alert' id='errorAlert'>$error</div>"; ?>

            <form action="add_remarks.php" method="POST" class="form-container">
                <input type="hidden" name="remarks_id" id="remarks_id">
                <div class="form-group">
                    <label for="remarks_name">New Remark</label>
                    <input type="text" name="remarks_name" id="remarks_name" class="form-control" required>
                </div>
                <button type="submit" class="btn-save">Save Remark</button>
            </form>
        </div>

        <div class="card search-card">
            <h2>Search Remarks</h2>
            <hr class="section-divider">
            <div class="form-inline mb-3">
                <select id="filterBy" class="mr-2">
                    <option value="id">ID</option>
                    <option value="remarks_name">Remarks</option>
                </select>
                <input type="text" id="searchInput" class="form-control" placeholder="Search...">
            </div>

            <h2>Existing Remarks</h2>
            <div class="table-responsive">
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
