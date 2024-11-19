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

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['building'])) {
        $building = trim($_POST['building']);
        $office = trim($_POST['office']);
        $room = trim($_POST['room']);
        $location_id = isset($_POST['location_id']) ? $_POST['location_id'] : null;

        if (!empty($building) && !empty($office) && !empty($room)) {
            // Check for duplicate entries, ignoring soft-deleted locations
            $checkSQL = "SELECT COUNT(*) FROM location 
                         WHERE building = :building AND office = :office AND room = :room 
                         AND location_id != :location_id AND deleted_id = 0";
            $stmt = $conn->prepare($checkSQL);
            $stmt->bindValue(':building', $building);
            $stmt->bindValue(':office', $office);
            $stmt->bindValue(':room', $room);
            $stmt->bindValue(':location_id', $location_id ?? 0);
            $stmt->execute();
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                $error = "Location already exists.";
            } else {
                if ($location_id) {
                    // Update existing location
                    $updateSQL = "UPDATE location SET building = :building, office = :office, room = :room 
                                  WHERE location_id = :location_id";
                    $stmt = $conn->prepare($updateSQL);
                    $stmt->bindValue(':building', $building);
                    $stmt->bindValue(':office', $office);
                    $stmt->bindValue(':room', $room);
                    $stmt->bindValue(':location_id', $location_id);
                    $stmt->execute();
                    $_SESSION['message'] = "Location updated successfully.";
                } else {
                    // Insert new location
                    $insertSQL = "INSERT INTO location (building, office, room) 
                                  VALUES (:building, :office, :room)";
                    $stmt = $conn->prepare($insertSQL);
                    $stmt->bindValue(':building', $building);
                    $stmt->bindValue(':office', $office);
                    $stmt->bindValue(':room', $room);
                    $stmt->execute();
                    $_SESSION['message'] = "New location added successfully.";
                }
                header("Location: add_location.php");
                exit;
            }
        } else {
            $error = "All fields are required.";
        }
    }

    if (isset($_POST['deleted_id'])) {
        $delete_id = $_POST['deleted_id'];
        // Soft delete instead of actual delete
        $softDeleteSQL = "UPDATE location SET deleted_id = 1 WHERE location_id = :location_id";
        $stmt = $conn->prepare($softDeleteSQL);
        $stmt->bindValue(':location_id', $delete_id);
        $stmt->execute();
        echo "Success";
        exit;
    }

    // Fetch only non-deleted locations
    $sql = "SELECT * FROM location WHERE deleted_id = 0";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Add Location</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <style>
        body {
			font-family: Arial, sans-serif;
			background-color: #f8f9fa;
		}
		.container {
			margin-top: -1.1rem !important;
			margin-left: 1.3rem !important;
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
			width: 530px;
			height: 190px;
			padding: 15px;
			position: relative;
		}
		.search-card {
			height: 420px;
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
			width: 80px;
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
			/*height: 38.35px;*/
			display: inline-block;
			padding: 7px 10px;
			padding-top: 7.5px;
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

		.pagination {
			justify-content: flex-end; 
			margin: 0;
		}
		.pagination .page-link {
			border: none; 
			font-size: 0.8rem; 
			padding: 0px 8px; 
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
            <h1>Add Location</h1>
            <hr class="section-divider">
            <?php if (isset($message)) echo "<div class='alert alert-success floating-alert' id='successAlert'>$message</div>"; ?>
            <?php if (isset($error)) echo "<div class='alert alert-danger floating-alert' id='errorAlert'>$error</div>"; ?>

            <form action="add_location.php" method="POST">
                <input type="hidden" name="location_id" id="location_id">

                <!-- Building Field -->
                <div class="form-group">
                    <label for="building">Building:</label>
                    <input type="text" name="building" id="building" class="form-control" required>
                </div>

                <!-- Office Field -->
                <div class="form-group">
                    <label for="office">Office:</label>
                    <input type="text" name="office" id="office" class="form-control" required>
                </div>

                <!-- Room Field with Button Next to It -->
                <div class="form-group">
                    <label for="room">Room:</label>
                    <input type="text" name="room" id="room" class="form-control" required>
                    <button type="submit" class="btn-save">Add Location</button>
                </div>
            </form>
        </div>

        <div class="card search-card">
            <h2>List of Locations</h2>
            <hr class="section-divider">
            <div class="form-inline">
                <select id="filterBy" class="form-control mr-2">
                    <option value="id">ID</option>
                    <option value="building">Building</option>
                    <option value="office">Office</option>
                    <option value="room">Room</option>
                </select>
                <input type="text" id="searchInput" class="form-control" placeholder="Search...">
            </div>

            <h2>Existing Locations</h2>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Building</th>
                            <th>Office</th>
                            <th>Room</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="locationTableBody">
                        <?php foreach ($locations as $location): ?>
                            <tr id="row-<?php echo htmlspecialchars($location['location_id']); ?>">
                                <td><?php echo htmlspecialchars($location['location_id']); ?></td>
                                <td><?php echo htmlspecialchars($location['building']); ?></td>
                                <td><?php echo htmlspecialchars($location['office']); ?></td>
                                <td><?php echo htmlspecialchars($location['room']); ?></td>
                                <td>
                                    <a href="#" onclick="editLocation(<?php echo htmlspecialchars($location['location_id']); ?>)">
                                        <img src="edit.png" alt="Edit" style="width:20px; cursor: pointer;">
                                    </a>
                                    <a href="#" onclick="softDelete(<?php echo htmlspecialchars($location['location_id']); ?>)">
                                        <img src="delete.png" alt="Delete" style="width:20px; cursor: pointer;">
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

        function editLocation(id) {
            const row = document.getElementById('row-' + id);
            const building = row.cells[1].innerText;
            const office = row.cells[2].innerText;
            const room = row.cells[3].innerText;

            document.getElementById('location_id').value = id;
            document.getElementById('building').value = building;
            document.getElementById('office').value = office;
            document.getElementById('room').value = room;
        }

        function softDelete(id) {
            if (confirm('Are you sure you want to delete this location?')) {
                $.post('add_location.php', { deleted_id: id }, function(response) {
                    if (response.trim() === "Success") {
                        document.getElementById('row-' + id).style.display = 'none';
                    } else {
                        alert('Failed to delete the location.');
                    }
                });
            }
        }

        $('#searchInput').on('input', function () {
            let filter = $('#filterBy').val();
            let query = $(this).val().trim().toLowerCase();

            $('#locationTableBody tr').each(function () {
                let text = $(this).find(`td:nth-child(${filter === 'id' ? 1 : filter === 'building' ? 2 : filter === 'office' ? 3 : 4})`).text().trim().toLowerCase();
                $(this).toggle(text.includes(query));
            });
        });
    </script>
</body>
</html>
