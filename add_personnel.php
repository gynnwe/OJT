<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

include 'conn.php';

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['firstname'])) {
        $firstname = trim($_POST['firstname']);
        $lastname = trim($_POST['lastname']);
        $office = trim($_POST['office']);
        $personnel_id = isset($_POST['personnel_id']) ? $_POST['personnel_id'] : null;

        if (!empty($firstname) && !empty($lastname) && !empty($office)) {
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
                                  office = :office WHERE personnel_id = :personnel_id";
                    $stmt = $conn->prepare($updateSQL);
                    $stmt->bindValue(':firstname', $firstname);
                    $stmt->bindValue(':lastname', $lastname);
                    $stmt->bindValue(':office', $office);
                    $stmt->bindValue(':personnel_id', $personnel_id);
                    $stmt->execute();
                    $_SESSION['message'] = "Personnel updated successfully.";
                } else {
                    // Insert new personnel
                    $insertSQL = "INSERT INTO personnel (firstname, lastname, office) 
                                  VALUES (:firstname, :lastname, :office)";
                    $stmt = $conn->prepare($insertSQL);
                    $stmt->bindValue(':firstname', $firstname);
                    $stmt->bindValue(':lastname', $lastname);
                    $stmt->bindValue(':office', $office);
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
			width: 555px;
			height: 200;
			padding: 15px;
			position: relative;
		}
		
		.search-card {
			height: 390px;
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
            color: #3A3A3A;
			font-weight: bold;
			font-size: 13px;
			margin-top: 5px;
        }
		h3 {
			color: #3A3A3A;
			font-weight: regular;
			font-size: 13px;
			margin-top: -10px;
			margin-bottom: 0px;
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
		.add-edit-card .form-control {
			margin-left: -200px;
		}
		
		.search-card {
			margin-top: -10px;
		}
		
		#searchInput {
			width: 257px;
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
			margin-top: -5px;
		}
		.table thead th {
			border-bottom: none;}
		
		.table th:nth-child(1) {
			width: 10%; 
		}

		th:nth-child(2) {
			width: 25%; 
		}

		th:nth-child(3) {
			width: 25%; 
		}

		th:nth-child(4) {
			width: 25%;
		}
		
		th:nth-child(5) {
			width: 10%;
			margin-left: 8px;
		}

		.table td {
			color:#646464 ; 
			font-weight :bold ;
			border-collapse: separate; 
			border-spacing: 10px 40px;
			border: none; 
			/*height: 33.18px;*/
			display: inline-block;
			padding: 0px 10px;
			padding-top: 5px;
		}

		.table td img {
			opacity: 75%;
			margin-bottom: 5px;
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
			width: 25%; 
		}

		td:nth-child(3) {
			width: 25%;
		}

		td:nth-child(4) {
			width: 25%;
		}
		
		td:nth-child(5) {
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
			background-color :#ebebeb ; 
		}

		tr {
			font-size: 13px;	
		}
		
		.empty-row, .no-personnel {
			height: 31.7px;
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

                <!-- Office Field with Button Next to It -->
                <div class="form-group">
                    <label for="office">Office:</label>
                    <input type="text" name="office" id="office" class="form-control" required>
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
                    <option value="office">Office</option>
                </select>
                <input type="text" id="searchInput" class="form-control" placeholder="Search...">
            </div>

            <h3>Existing Personnel</h3>
            <div class="table-responsive">

				<?php
				// Assuming $personnel is your array of personnel
				$maxRows = 5; // Maximum rows per page
				$totalEntries = count($personnel); // Total number of entries
				$totalPages = ceil($totalEntries / $maxRows); // Total number of pages

				// Get the current page from query parameters, default to 1
				$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
				$currentPage = max(1, min($currentPage, $totalPages)); // Ensure current page is within valid range

				// Calculate the starting index for the current page
				$startIndex = ($currentPage - 1) * $maxRows;

				// Slice the personnel array to get only the entries for the current page
				$currentPersonnel = array_slice($personnel, $startIndex, $maxRows);
				?>

				<table class="table table-striped">
					<thead>
						<tr>
							<th>ID</th>
							<th>First Name</th>
							<th>Last Name</th>
							<th>Office</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody id="personnelTableBody">
						<?php 
						if ($totalEntries === 0): ?>
							<tr class="no-personnel"><td></td><td colspan="5">No personnel available.</td></tr>
							<?php 
							// Add empty rows to make a total of 5
							for ($j = 1; $j < $maxRows; $j++): ?>
								<tr class="empty-row"><td colspan="5"></td></tr>
							<?php endfor; 
						else:
							// Display personnel for the current page
							foreach ($currentPersonnel as $person): ?>
								<tr id="row-<?php echo htmlspecialchars($person['personnel_id']); ?>">
									<td><?php echo htmlspecialchars($person['personnel_id']); ?></td>
									<td><?php echo htmlspecialchars($person['firstname']); ?></td>
									<td><?php echo htmlspecialchars($person['lastname']); ?></td>
									<td><?php echo htmlspecialchars($person['office']); ?></td>
									<td>
										<a href="#" onclick="editPersonnel(<?php echo htmlspecialchars($person['personnel_id']); ?>)">
											<img src="edit.png" alt="Edit" style="width:20px; cursor: pointer;">
										</a>
										<a href="#" onclick="softDelete(<?php echo htmlspecialchars($person['personnel_id']); ?>)">
											<img src="delete.png" alt="Delete" style="width:20px; cursor: pointer;">
										</a>
									</td>
								</tr>
							<?php endforeach;

							// Add empty rows if there are fewer than 5 entries on this page
							for ($j = count($currentPersonnel); $j < $maxRows; $j++): ?>
								<tr class="empty-row"><td colspan="5"></td></tr> <!-- Empty row with class -->
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

			$('#searchInput').on('keyup', function() {
				const filterBy = $('#filterBy').val();
				const value = $(this).val().toLowerCase();

				$('#personnelTableBody tr').filter(function() {
					const match = $(this).find(`td:nth-child(${filterBy === 'id' ? 1 : filterBy === 'firstname' ? 2 : filterBy === 'lastname' ? 3 : 4})`)
						.text().toLowerCase().indexOf(value) > -1;
					$(this).toggle(match);
				});
			});
		});

        function editPersonnel(id) {
            const row = document.getElementById('row-' + id);
            const firstname = row.cells[1].innerText;
            const lastname = row.cells[2].innerText;
            const office = row.cells[3].innerText;

            document.getElementById('personnel_id').value = id;
            document.getElementById('firstname').value = firstname;
            document.getElementById('lastname').value = lastname;
            document.getElementById('office').value = office;
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

<script>
document.addEventListener("DOMContentLoaded", function () {
    const firstNameInput = document.getElementById("firstname");
    const lastNameInput = document.getElementById("lastname");
    const officeInput = document.getElementById("office");

    // Function to allow letters, spaces, and capitalize each word for First Name and Last Name
    function allowTextSpacesAndCapitalize(input, maxLength) {
        input.addEventListener("input", function () {
            input.value = input.value
                .replace(/[^A-Za-z ]/g, "") // Allow letters and spaces
                .slice(0, maxLength) // Enforce max length
                .toLowerCase()
                .replace(/\b\w/g, char => char.toUpperCase()); // Capitalize first letter
        });

        input.addEventListener("paste", function (e) {
            e.preventDefault();
            const pastedText = e.clipboardData.getData("text");
            input.value = pastedText
                .replace(/[^A-Za-z ]/g, "")
                .slice(0, maxLength)
                .toLowerCase()
                .replace(/\b\w/g, char => char.toUpperCase());
        });
    }

    // Function to allow text, numbers, spaces, and auto-capitalize after spaces for Office
    function allowTextNumbersAutoCapitalize(input, maxLength) {
        input.addEventListener("input", function () {
            input.value = input.value
                .replace(/[^A-Za-z0-9 ]/g, "") // Allow letters, numbers, and spaces
                .slice(0, maxLength) // Enforce max length
                .replace(/\b\w/g, char => char.toUpperCase()); // Capitalize first letter after spaces
        });

        input.addEventListener("paste", function (e) {
            e.preventDefault();
            const pastedText = e.clipboardData.getData("text");
            input.value = pastedText
                .replace(/[^A-Za-z0-9 ]/g, "")
                .slice(0, maxLength)
                .replace(/\b\w/g, char => char.toUpperCase());
        });
    }

    // Apply the restrictions
    allowTextSpacesAndCapitalize(firstNameInput, 20); // First Name: Letters and spaces, max 20
    allowTextSpacesAndCapitalize(lastNameInput, 20);  // Last Name: Letters and spaces, max 20
    allowTextNumbersAutoCapitalize(officeInput, 20);  // Office: Letters, numbers, spaces, auto-capitalize
});
</script>


</body>
</html>
