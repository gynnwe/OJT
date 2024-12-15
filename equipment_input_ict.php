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

    // Fetch all non-deleted equipment types for the dropdown
    $sql = "SELECT equip_type_id, equip_type_name FROM equipment_type WHERE deleted_id = 0";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $equipment_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all non-deleted locations for the dropdown
    $sql = "SELECT location_id, building, office, room FROM location WHERE deleted_id = 0";  // Updated query
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle AJAX request for fetching models based on equipment type
    if (isset($_GET['equip_type_id'])) {
        $equip_type_id = $_GET['equip_type_id'];
        $sqlModels = "SELECT model_id, model_name FROM model WHERE equip_type_id = :equip_type_id AND (deleted_id IS NULL OR deleted_id = 0)";
        $stmt = $conn->prepare($sqlModels);
        $stmt->bindParam(':equip_type_id', $equip_type_id);
        $stmt->execute();
        $models = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        echo json_encode($models);
        exit;
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Equipment</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
	<script src="scripts.js" defer ></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            background-color: transparent !important;
        }

        .container {
            margin-top: 3.65rem !important;
            margin-left: 2.6rem !important;
            display: flex;
            gap: 20px;
        }

        .card {
            background-color: #ffffff;
            border-radius: 24px !important;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 15px;
            border: none;
        }

        .register-card {
            flex: 1;
            max-width: 400px;
        }

        .list-card {
            flex: 2;
        }

        h5 {
            color: #3A3A3A;
            font-weight: bold !important;
            font-size: 14px !important;
        }

        .form-control, .form-select {
            width: 100%;
            font-size: 12px;
            margin-bottom: 15px;
        }
		
		.form-select {
			background-color: #d1d1d1 !important;
			border: none !important;
			color: #646464 !important;
			border-radius: 14px;
		}
		
		.register-card .form-select {
			padding: 10px !important;
		}
		
		.form-control {
			border: 2px solid #646464;
            border-radius: 14px;
            color: #646464;
		}
		
		.register-card .form-control {
			padding: 17px 10px 17px 13px !important;
		}
		
		.list-card .form-control {
			padding: 10px !important;
			width: 250px !important;
		}

		.list-card .form-select {
			padding: 7px !important;
			width: 250px !important;
		}
		
        .form-control::placeholder,
        .form-select::placeholder {
            color: #646464 !important;
        }

        button.btn-submit {
            width: 100%;
            padding: 10px;
            background-color: #a81519;
            color: white;
            font-weight: bold;
            font-size: 12px;
            border: none;
            border-radius: 14px;
            cursor: pointer;
            margin-bottom: 10px;
        }
		
		h5 {
			color: #3A3A3A;
			font-weight: bold !important;
			font-size: 13px !important;
			padding-top: 2px;
		}

        button.btn-submit:hover {
            background-color: #8b0000;
        }

        .search-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        .section-divider1 {
            border: none;
            height: 1px;
            background-color: #ddd;
            margin-top: 8px;
			margin-bottom: 19px;
        }

        .section-divider2 {
            border: none;
            height: 1px;
            background-color: #ddd;
            margin: 20px 0;
        }
		
		.table-responsive {
			border-radius: 10px;
			overflow: hidden;
		}

		.table {
			width: 100%;
			border: none;
		}

		.table th,
		.table td {
			color: #646464;
			padding: 10px;
			text-align: left;
			font-size: 12px;
		}

		.table th {
			font-weight: normal;
		}

		.table tbody tr:hover {
			background-color: #ebebeb !important;
		}

    </style>
</head>

<body>
    <div class="container">
        <!-- Register Equipment Section -->
        <div class="card register-card">
            <h5>Register Equipment</h5>
            <hr class="section-divider1">
            <form action="equipment_process.php" method="POST">
                <select name="location_id" id="location_id" class="form-select" required>
                    <option value="" disabled selected>Location</option>
                    <?php if (!empty($locations)) : ?>
                        <?php foreach ($locations as $location) : ?>
                            <option value="<?php echo htmlspecialchars($location['location_id']); ?>">
                                <?php echo htmlspecialchars($location['building'] . " - " . $location['office'] . " - " . $location['room']); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <option value="">No locations available</option>
                    <?php endif; ?>
                </select>

                <select name="equipment_type" id="equipment_type" class="form-select" required>
                    <option value="" disabled selected>Equipment Type</option>
                    <?php if (!empty($equipment_types)) : ?>
                        <?php foreach ($equipment_types as $type) : ?>
                            <option value="<?php echo htmlspecialchars($type['equip_type_id']); ?>">
                                <?php echo htmlspecialchars($type['equip_type_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <option value="">No equipment types available</option>
                    <?php endif; ?>
                </select>

                <input type="text" name="equip_name" id="equip_name" class="form-control" placeholder="Equipment Name" required>

                <input type="text" name="property_num" id="property_num" class="form-control" placeholder="Property Number" required>

                <select name="model_id" id="model_name" class="form-select" required>
                    <option value="" disabled selected>Brand/Model Name</option>
                    <!-- Options will be populated dynamically based on selected equipment type -->
                </select>

                <select name="status" id="status" class="form-select" required>
                    <option value="" disabled selected>Status</option>
                    <option value="Serviceable">Serviceable</option>
                    <option value="Non-serviceable">Non-serviceable</option>
                </select>

                <input type="date" name="date_purchased" id="date_purchased" class="form-control" placeholder="Date Purchased" required max="<?php echo date('Y-m-d'); ?>">
                <hr class="section-divider2">
                <button type="submit" class="btn-submit">Submit</button>
            </form>
        </div>

        <!-- Registered Equipment List -->
        <div class="card list-card">
            <h5>Registered Equipments</h5>
            <hr class="section-divider1">
            <div class="search-bar">
                <select class="form-select" id="filterBy">
                    <option value="all">All</option>
                    <option value="monitor">Monitor</option>
                    <option value="printer">Printer</option>
                </select>
                <input type="text" class="form-control" placeholder="Search by Name">
            </div>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Equipment Name</th>
                            <th>Property Number</th>
                            <th>Status</th>
                            <th>Date</th>
							<th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($registered_equipments)) : ?>
                            <?php foreach ($registered_equipments as $equipment) : ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($equipment['equip_name']); ?></td>
                                    <td><?php echo htmlspecialchars($equipment['property_num']); ?></td>
                                    <td><?php echo htmlspecialchars($equipment['status']); ?></td>
                                    <td><?php echo htmlspecialchars($equipment['date_purchased']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="4">No registered equipment available</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>

