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
			background-color: transparent !important;
		}
		.container {
			margin-top: 3.65rem !important;
			margin-left: 2.6rem !important;
		}
		.card {
			background-color: #ffffff;
			border-radius: 24px;
			box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
			margin-bottom: 20px;
			padding: 15px;
			border: none;
		}
		.add-edit-card {
			width: 800px;
			height: 145px;
			padding: 15px;
			position: relative;
		}
		.search-card {
			height: 410px;
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
			color: #3A3A3A;
			font-weight: bold;
			font-size: 13px;
		}
		h3 {
			color: #3A3A3A;
			font-weight: 500 !important;
			font-size: 13px;
		}	
		.section-divider {
			border: none;
            height: 1px;
            background-color: #ddd;
            margin-top: 5px;
			margin-bottom: 10px;
		}
		.form-group {
			display: flex;
			align-items: center;
			gap: 15px;
			margin-bottom: 5px;
		}
		label {
			padding-top: 5px;
		}
		.form-group label {
			font-size: 13px;
			width: 300px;
		}
		.form-control {
			width: 257px;
			height: 33px;
			border: 2px solid #646464; 
			border-radius: 14px; 
			color: #646464; 
			font-size: 12px;
		}

		#searchInput {
			width: 257px;
		}
		
		#model_name {
			margin-top: 0px;
			margin-left: -30px;
		}
		
		#equip_type_id {
			width: 257px; 
			height: 33px; 
			background-color: #d1d1d1; 
			border-radius: 14px; 
			color:#646464 ; 
			font-size :13px ; 
			border:none; 
			margin-top: 0px;
			margin-left: -30px;
			}
		.btn-save {
			width: 130px; 
			height: 33px; 
			background-color: #a81519; 
			color: white; 
			font-weight: bold; 
			font-size: 12px; 
			border: none; 
			border-radius: 14px; 
			margin-top: 0px;
			margin-left: 82px;
		}
		.btn-save:hover {
			background-color: #E3595C; 
		}
		#filterBy {
			padding-left: 15px;
            width: 257px; 
			height: 33px; 
			background-color: #d1d1d1; 
			border-radius: 14px; 
			color:#646464 ; 
			font-size :13px ; 
			border:none; 
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
		.table {
			width: 100%; 
			border:none;
		}
		.table th {
			text-align:left ;
			font-size :13px ;
			font-weight: normal;
			color:#646464 ;
			border: none ;
			display: inline-block;
			margin-top: -6px;
			margin-bottom: -2px;
		}
		
		.table thead th {
			border-bottom: none;}

		.table th:nth-child(1) {
			width: 10%; 
		}
		th:nth-child(2) {
			width: 35%; 
		}

		th:nth-child(3) {
			width: 35%; 
		}

		th:nth-child(4) {
			width: 10%;
			margin-left: 10px;
		}

		.table td {
			color:#646464 ; 
			font-weight :bold ;
			border-collapse: separate; 
			border-spacing: 10px 40px;
			border: none; 
			display: inline-block;
			padding: 6px 10px;
		}

		.table td img {
			opacity: 75%;
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

		td:nth-child(1) {
			width: 10%;
		}

		td:nth-child(2) {
			width: 35%; 
		}

		td:nth-child(3) {
			width: 35%;

		}

		td:nth-child(4) {
			width: 10%;
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
			background-color :#ebebeb; 
		}

		tr {
			font-size: 13px;
		}
		
		.empty-row {
			height: 33.8px;
		}
		
		.pagination .disabled .page-link {
			pointer-events: none;
			color: #ccc !important;
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
		
		.pagination .page-link:hover {
			color: #b86e63;
		}

		.page-link {
			color: #474747; }
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
                    <label for="equip_type_id">Equipment Type</label>
                    <select name="equip_type_id" id="equip_type_id" class="form-control" required>
                        <?php foreach ($equipment_types as $type): ?>
                            <option value="<?php echo $type['equip_type_id']; ?>"><?php echo htmlspecialchars($type['equip_type_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- New Model and Save Button Row -->
                <div class="form-group">
                    <label for="model_name">New Model for Chosen Equipment Type</label>
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

            <h3>Existing Models</h2>
			
            <div class="table-responsive">
                <?php
					// Assuming $models is your array of models
					$maxRows = 5; // Maximum rows per page
					$totalEntries = count($models); // Total number of entries
					$totalPages = ceil($totalEntries / $maxRows); // Total number of pages

					// Get the current page from query parameters, default to 1
					$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
					$currentPage = max(1, min($currentPage, $totalPages)); // Ensure current page is within valid range

					// Calculate the starting index for the current page
					$startIndex = ($currentPage - 1) * $maxRows;

					// Slice the models array to get only the entries for the current page
					$currentModels = array_slice($models, $startIndex, $maxRows);
					?>

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
							<?php 
							// If there are no models, show the message in the first row
							if ($totalEntries === 0): ?>
								<tr><td></td><td colspan="3">No models registered.</td></tr>
								<?php 
								// Add empty rows to make a total of 5
								for ($j = 1; $j < $maxRows; $j++): ?>
									<tr class="empty-row"><td colspan="4"></td></tr>
								<?php endfor; 
							else:
								// Display models for the current page
								foreach ($currentModels as $model): ?>
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
								<?php endforeach;

								// Add empty rows if there are fewer than 5 entries on this page
								for ($j = count($currentModels); $j < $maxRows; $j++): ?>
									<tr class="empty-row"><td colspan="4"></td></tr> <!-- Empty row with class -->
								<?php endfor;
							endif; ?>
						</tbody>
					</table>

				<nav>
					<ul class="pagination">
						<?php if ($currentPage > 1): ?>
							<li class="page-item"><a class="page-link" href="?page=<?php echo $currentPage - 1; ?>">Previous</a></li>
						<?php else: ?>
							<li class="page-item disabled"><span class="page-link">Previous</span></li>
						<?php endif; ?>

						<?php if ($currentPage < $totalPages): ?>
							<li class="page-item"><a class="page-link" href="?page=<?php echo $currentPage + 1; ?>">Next</a></li>
						<?php else: ?>
							<li class="page-item disabled"><span class="page-link">Next</span></li>
						<?php endif; ?>
					</ul>
				</nav>
			</div>
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
