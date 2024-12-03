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
			width: 460px;
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
			font-weight: regular;
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
			display: block;
			gap: 15px;
			margin-bottom: 5px;
		}
		label {
			padding-top: 5px;
		}
		.form-group label {
			font-size: 13px;
		}
		.form-control {
			height: 33px;
			border: 2px solid #646464; 
			border-radius: 14px; 
			color: #646464; 
			font-size: 12px;
			display: inline-block;
		}
		#remarks_name {
			width: 257px;
			display: inline-block;
		}
		#searchInput {
			width: 257px;
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
			display: inline-block;
			margin-left: 23px;
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
			width: 70%; 
		}

		th:nth-child(3) {
			width: 10%; 
			margin-left: 5px;
		}


		.table td {
			color:#646464 ; 
			font-weight :bold ;
			border-collapse: separate; 
			border-spacing: 10px 40px;
			border: none; 
			/*height: 38.35px;*/
			display: inline-block;
			padding: 6px 10px;
			padding-top: 6px;
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
			width: 70%; 
		}

		td:nth-child(3) {
			width: 14%;

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
		
		.empty-row, .no-remarks {
			height: 35px;
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
            <h1>Add/Edit Remark</h1>
            <hr class="section-divider">
            <?php if (isset($message)) echo "<div class='alert alert-success floating-alert' id='successAlert'>$message</div>"; ?>
            <?php if (isset($error)) echo "<div class='alert alert-danger floating-alert' id='errorAlert'>$error</div>"; ?>

            <form action="add_remarks.php" method="POST" class="form-container">
                <input type="hidden" name="remarks_id" id="remarks_id">
                <div class="form-group">
                    <label for="remarks_name">New Remark</label>
				</div>
                    <input type="text" name="remarks_name" id="remarks_name" class="form-control" required>
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

            <h3>Existing Remarks</h3>
            <div class="table-responsive">
                
				
				<?php
				// Assuming $remarks is your array of remarks
				$maxRows = 5; // Maximum rows per page
				$totalEntries = count($remarks); // Total number of entries
				$totalPages = ceil($totalEntries / $maxRows); // Total number of pages

				// Get the current page from query parameters, default to 1
				$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
				$currentPage = max(1, min($currentPage, $totalPages)); // Ensure current page is within valid range

				// Calculate the starting index for the current page
				$startIndex = ($currentPage - 1) * $maxRows;

				// Slice the remarks array to get only the entries for the current page
				$currentRemarks = array_slice($remarks, $startIndex, $maxRows);
				?>

				<table class="table table-striped">
					<thead>
						<tr>
							<th>ID</th>
							<th>Remarks</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody id="remarksTableBody">
						<?php 
						if ($totalEntries === 0): ?>
							<tr class="no-remarks"><td></td><td colspan="3">No remarks added.</td></tr>
							<?php 
							// Add empty rows to make a total of 5
							for ($j = 1; $j < $maxRows; $j++): ?>
								<tr class="empty-row"><td colspan="3"></td></tr>
							<?php endfor; 
						else:
							// Display remarks for the current page
							foreach ($currentRemarks as $remark): ?>
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
							<?php endforeach;

							// Add empty rows if there are fewer than 5 entries on this page
							for ($j = count($currentRemarks); $j < $maxRows; $j++): ?>
								<tr class="empty-row"><td colspan="3"></td></tr> <!-- Empty row with class -->
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
