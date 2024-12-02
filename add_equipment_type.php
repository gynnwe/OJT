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

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['equip_type_name'])) {
        $equip_type_name = trim($_POST['equip_type_name']);
        $equip_type_id = isset($_POST['equip_type_id']) ? $_POST['equip_type_id'] : null;

        if (!empty($equip_type_name)) {
            $checkSQL = "SELECT COUNT(*) FROM equipment_type WHERE equip_type_name = :equip_type_name AND deleted_id = 0 AND (:equip_type_id IS NULL OR equip_type_id != :equip_type_id)";
            $stmt = $conn->prepare($checkSQL);
            $stmt->bindParam(':equip_type_name', $equip_type_name);
            $stmt->bindParam(':equip_type_id', $equip_type_id);
            $stmt->execute();
            $count = $stmt->fetchColumn();

            if ($count == 0) {
                if ($equip_type_id) {
                    $updateSQL = "UPDATE equipment_type SET equip_type_name = :equip_type_name WHERE equip_type_id = :equip_type_id";
                    $stmt = $conn->prepare($updateSQL);
                    $stmt->bindParam(':equip_type_name', $equip_type_name);
                    $stmt->bindParam(':equip_type_id', $equip_type_id);
                } else {
                    $insertSQL = "INSERT INTO equipment_type (equip_type_name) VALUES (:equip_type_name)";
                    $stmt = $conn->prepare($insertSQL);
                    $stmt->bindParam(':equip_type_name', $equip_type_name);
                }
                if ($stmt->execute()) {
                    $_SESSION['message'] = $equip_type_id ? "Equipment type updated successfully." : "New equipment type added successfully.";
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
	<style>
		body {
			background-color: transparent !important;
		}
		
		.equipment-type-form {
			background-color: #FFFFFF;
			width: 471px;
			border-radius: 24px;
			box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
			padding: 15px;
			margin-bottom: 10px;
			position: relative;
		}

		.equipment-type-list {
			background-color: #FFFFFF;
			width: 100%;
			height: 490px;
			border-radius: 24px;
			box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
			padding: 15px;
		}

		.equipment-type-form h1,
		.equipment-type-list h1 {
			color: #3A3A3A;
			font-weight: bold;
			font-size: 13px;
		}

		.equipment-type-form h2,
		.equipment-type-list h2 {
			color: #3A3A3A;
			font-weight: regular;
			font-size: 13px;
		}

		.equipment-type-form hr,
		.equipment-type-list hr {
			margin-top: 15px;
			margin-bottom: 7px;
			border: 0;
			height: 1px;
			background-color: rgba(0, 0, 0, 0.2);
		}

		.equipment-type-form .form-group label,
		.equipment-type-list .form-inline label {
			color: #3A3A3A;
			font-size: 13px;
		}

		#content-area {
			margin-top: 10px;
			padding-top: 15px;
			background-color: #fff;
			height: 100%;
			padding-left: 40px;
		}

		#equip_type_name {
			width: 257px;
			height: 33px;
			border: 2px solid #646464; 
			border-radius: 14px; 
			color: #646464; 
			font-size: 12px; 
		}

		.btn-primary {
			width: 157px; 
			height: 33px; 
			background-color: #a81519; 
			color: white; 
			font-weight: bold; 
			font-size: 12px; 
			border: none; 
			border-radius: 14px; 
		}

		.btn-primary:hover {
			background-color: #E3595C; 
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


		#searchInput {
			width: 257px; 
			height: 33px; 
			border-radius: 14px; 
			border: 2px solid #646464; 
			font-size: 12px; 
		}

		#filterBy { 
			width: 257px; 
			height: 33px; 
			background-color: #d1d1d1; 
			border-radius: 14px; 
			color:#646464 ; 
			font-size :13px ; 
			border:none; 
		}

		.table {
			width: 100%; 
			border:none;
		}

		.table thead th {
			border-bottom: none;}
		.table th {
			text-align:left ;
			font-size :13px ;
			font-weight: normal;
			color:#646464 ;
			border: none ;
			display: inline-block;
			margin-top: -5px;
		}

		.table th:nth-child(1) {
			width: 10%; 
		}

		th:nth-child(2) {
			width: 60%;
		}

		th:nth-child(3) {
			width: 20%;
			margin-left: 5px;
		}

		.table td {
			color:#646464 ; 
			font-weight :bold ;
			border-collapse: separate; 
			border-spacing: 10px 40px;
			border: none;
			display: inline-block;
			padding: 11px 10px;
			padding-top: 12px;
		}

		.table td img {
			opacity: 75%;
		}

		td:nth-child(1) {
			width: 10%;
		}

		td:nth-child(2) {
			width: 60%;
		}

		td:nth-child(3) {
			width: 20%;
		}

		td a img[src='edit.png'], td a img[src='delete.png'] {
			transition: transform 0.3s ease-in-out;
		}

		td a img[src='edit.png']:hover {
			transform: scale(1.1);
		}

		td a img[src='delete.png']:hover {
			transform: scale(1.2);
		}

		table tbody {
			border-spacing: 15px 155px;
			border-radius: 14px; 
			margin: 20 -20px;
		}

		.table tbody tr:nth-child(odd), .table tbody tr:nth-child(even) {
			background-color: white;
			border: 1px solid #DFDFDF;
			border-radius: 14px; 
			display: block;
			width: 100%;
			margin-top: 5px;
		}

		.table tbody tr:hover {
			background-color :#ebebeb ; 
		}

		tr[id^="row-"] {
			font-size: 13px;	
		}

		.pagination {
			justify-content: flex-end; 
			margin-top: -5.2px;
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
<div class="container mt-5">
    <div class="equipment-type-form">
        <h1>Add/Edit Equipment Type</h1>
        <?php
            if (isset($message)) {
                echo "<div class='alert alert-success floating-alert' id='successAlert'>$message</div>";
            }
            ?> 
		<hr style="border: 0; height: 1px; background-color: rgba(0, 0, 0, 0.2);">
		<?php
            if (isset($error)) {
                echo "<div class='alert alert-danger floating-alert' id='errorAlert'>$error</div>";
            }
            ?>
        <form action="add_equipment_type.php" method="POST">
            <input type="hidden" name="equip_type_id" id="equip_type_id">
            <div class="form-group">
                <label for="equip_type_name">Equipment Type Name</label>
                <div style="display: flex; align-items: center;">
                    <input type="text" name="equip_type_name" id="equip_type_name" class="form-control" required style="margin-right: 10px;">
                    <button type="submit" class="btn btn-primary">Save Equipment Type</button>
                </div>
            </div>
        </form>
    </div>

    <div class="equipment-type-list">
        <h1>List of Equipment Types</h1>
        <hr style="border: 0; height: 1px; background-color: rgba(0, 0, 0, 0.2);">
        <h2>Search Equipment Type</h2>
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
                                <a href="#" onclick="editEquipment(<?php echo $type['equip_type_id']; ?>, '<?php echo htmlspecialchars($type['equip_type_name']); ?>')">
                                    <img src="edit.png" alt="Edit" style="width:20px; cursor: pointer;">
                                </a>
                                <a href="#" onclick="softDelete(<?php echo $type['equip_type_id']; ?>)">
                                    <img src="delete.png" alt="Delete" style="width:20px; cursor: pointer;">
                                </a>
                            </td>
							
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3">No equipment types available.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

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
