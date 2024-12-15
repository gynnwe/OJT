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

    // Handle Delete Operation
    if (isset($_POST['delete_equipment'])) {
        $equipment_id = $_POST['equipment_id'];
        $sql = "UPDATE equipment SET deleted_id = 1 WHERE equipment_id = :equipment_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':equipment_id', $equipment_id);
        $stmt->execute();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // Handle Update Operation
    if (isset($_POST['update_equipment'])) {
        $sql = "UPDATE equipment SET 
                location_id = :location_id,
                equip_type_id = :equip_type_id,
                model_id = :model_id,
                equip_name = :equip_name,
                property_num = :property_num,
                status = :status,
                date_purchased = :date_purchased
                WHERE equipment_id = :equipment_id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':location_id', $_POST['location_id']);
        $stmt->bindParam(':equip_type_id', $_POST['equipment_type']);
        $stmt->bindParam(':model_id', $_POST['model_id']);
        $stmt->bindParam(':equip_name', $_POST['equip_name']);
        $stmt->bindParam(':property_num', $_POST['property_num']);
        $stmt->bindParam(':status', $_POST['status']);
        $stmt->bindParam(':date_purchased', $_POST['date_purchased']);
        $stmt->bindParam(':equipment_id', $_POST['equipment_id']);
        $stmt->execute();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // Fetch equipment for editing if edit_id is set
    $edit_equipment = null;
    if (isset($_GET['edit_id'])) {
        $edit_id = $_GET['edit_id'];
        $sql = "SELECT * FROM equipment WHERE equipment_id = :edit_id AND deleted_id = 0";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':edit_id', $edit_id);
        $stmt->execute();
        $edit_equipment = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Fetch all non-deleted equipment types for the dropdown
    $sql = "SELECT equip_type_id, equip_type_name FROM equipment_type WHERE deleted_id = 0";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $equipment_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all non-deleted locations for the dropdown
    $sql = "SELECT location_id, building, office, room FROM location WHERE deleted_id = 0";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch registered equipment with joined data
    $sql = "SELECT e.*, et.equip_type_name, m.model_name, l.building, l.office, l.room 
            FROM equipment e 
            LEFT JOIN equipment_type et ON e.equip_type_id = et.equip_type_id 
            LEFT JOIN model m ON e.model_id = m.model_id 
            LEFT JOIN location l ON e.location_id = l.location_id 
            WHERE e.deleted_id = 0 
            ORDER BY e.equipment_id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $registered_equipments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle AJAX request for fetching models based on equipment type
    if (isset($_GET['equip_type_id'])) {
        $equip_type_id = $_GET['equip_type_id'];
        $sqlModels = "SELECT DISTINCT model_id, model_name FROM model WHERE equip_type_id = :equip_type_id AND deleted_id = 0 ORDER BY model_name";
        $stmt = $conn->prepare($sqlModels);
        $stmt->bindParam(':equip_type_id', $equip_type_id);
        $stmt->execute();
        $models = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(array_values(array_unique($models, SORT_REGULAR)));
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

        .action-btn {
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
        }

        .action-btn img {
            width: 20px;
            height: 20px;
            margin: 0 5px;
        }

        .action-btn:hover {
            opacity: 0.8;
        }
        
        .btn-cancel {
            width: 100%;
            height: 35px;
            background: #6c757d;
            border-radius: 14px;
            cursor: pointer;
            color: white;
            font-weight: 500;
            margin-top: 10px;
            text-decoration: none;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .btn-cancel:hover {
            background: #5a6268;
            color: white;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Register Equipment Section -->
        <div class="card register-card">
            <h5>Register Equipment</h5>
            <hr class="section-divider1">
            <form action="<?php echo isset($edit_equipment) ? 'equipment_input_ict.php' : 'equipment_process.php'; ?>" method="POST">
                <?php if (isset($edit_equipment)): ?>
                    <input type="hidden" name="equipment_id" value="<?php echo $edit_equipment['equipment_id']; ?>">
                <?php endif; ?>

                <select name="location_id" id="location_id" class="form-select" required>
                    <option value="" disabled <?php echo !isset($edit_equipment) ? 'selected' : ''; ?>>Location</option>
                    <?php foreach ($locations as $location): ?>
                        <option value="<?php echo htmlspecialchars($location['location_id']); ?>"
                                <?php echo (isset($edit_equipment) && $location['location_id'] == $edit_equipment['location_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($location['building'] . " - " . $location['office'] . " - " . $location['room']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="equipment_type" id="equipment_type" class="form-select" required>
                    <option value="" disabled <?php echo !isset($edit_equipment) ? 'selected' : ''; ?>>Equipment Type</option>
                    <?php foreach ($equipment_types as $type): ?>
                        <option value="<?php echo htmlspecialchars($type['equip_type_id']); ?>"
                                <?php echo (isset($edit_equipment) && $type['equip_type_id'] == $edit_equipment['equip_type_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type['equip_type_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <input type="text" name="equip_name" id="equip_name" class="form-control" 
                       placeholder="Equipment Name" required
                       value="<?php echo isset($edit_equipment) ? htmlspecialchars($edit_equipment['equip_name']) : ''; ?>">

                <input type="text" name="property_num" id="property_num" class="form-control" 
                       placeholder="Property Number" required
                       value="<?php echo isset($edit_equipment) ? htmlspecialchars($edit_equipment['property_num']) : ''; ?>">

                <select name="model_id" id="model_name" class="form-select" required>
                    <option value="" disabled <?php echo !isset($edit_equipment) ? 'selected' : ''; ?>>Brand/Model Name</option>
                    <?php if (isset($edit_equipment)): ?>
                        <?php 
                        $sqlModels = "SELECT model_id, model_name FROM model WHERE equip_type_id = :equip_type_id AND deleted_id = 0";
                        $stmt = $conn->prepare($sqlModels);
                        $stmt->bindParam(':equip_type_id', $edit_equipment['equip_type_id']);
                        $stmt->execute();
                        $models = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($models as $model): 
                        ?>
                            <option value="<?php echo htmlspecialchars($model['model_id']); ?>"
                                    <?php echo ($model['model_id'] == $edit_equipment['model_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($model['model_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>

                <select name="status" id="status" class="form-select" required>
                    <option value="" disabled <?php echo !isset($edit_equipment) ? 'selected' : ''; ?>>Status</option>
                    <option value="Serviceable" <?php echo (isset($edit_equipment) && $edit_equipment['status'] == 'Serviceable') ? 'selected' : ''; ?>>Serviceable</option>
                    <option value="Non-serviceable" <?php echo (isset($edit_equipment) && $edit_equipment['status'] == 'Non-serviceable') ? 'selected' : ''; ?>>Non-serviceable</option>
                </select>

                <input type="date" name="date_purchased" id="date_purchased" class="form-control" 
                       placeholder="Date Purchased" required max="<?php echo date('Y-m-d'); ?>"
                       value="<?php echo isset($edit_equipment) ? $edit_equipment['date_purchased'] : ''; ?>">

                <hr class="section-divider2">
                <?php if (isset($edit_equipment)): ?>
                    <button type="submit" name="update_equipment" class="btn-submit">Update Equipment</button>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn-cancel">Cancel</a>
                <?php else: ?>
                    <button type="submit" class="btn-submit">Register Equipment</button>
                <?php endif; ?>
            </form>
            <?php
            if (isset($_POST['update_equipment']) && $stmt->rowCount() > 0) {
                echo "<script>alert('Equipment updated successfully!');</script>";
            }
            ?>
        </div>

        <!-- Registered Equipment List -->
        <div class="card list-card">
            <h5>Registered Equipments</h5>
            <hr class="section-divider1">
            <div class="search-bar">
                <select class="form-select" id="filterBy">
                    <option value="all">All</option>
                    <?php foreach ($equipment_types as $type): ?>
                        <option value="<?php echo htmlspecialchars($type['equip_type_name']); ?>">
                            <?php echo htmlspecialchars($type['equip_type_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" class="form-control" placeholder="Search by Name">
            </div>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Equipment Name</th>
                            <th>Property Number</th>
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
                                    <td><?php echo htmlspecialchars($equipment['date_purchased']); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="equipment_id" value="<?php echo $equipment['equipment_id']; ?>">
                                            <a href="?edit_id=<?php echo $equipment['equipment_id']; ?>" class="action-btn">
                                                <img src="edit.png" alt="Edit" title="Edit">
                                            </a>
                                            <button type="submit" name="delete_equipment" class="action-btn" onclick="return confirm('Are you sure you want to delete this equipment?')">
                                                <img src="delete.png" alt="Delete" title="Delete">
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="4" class="text-center">No equipment registered</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Equipment type filter and search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('.search-bar input');
            const filterSelect = document.getElementById('filterBy');
            const table = document.querySelector('.table');

            function filterTable() {
                const searchTerm = searchInput.value.toLowerCase();
                const filterValue = filterSelect.value.toLowerCase();
                const rows = Array.from(table.getElementsByTagName('tr')).slice(1); // Skip header

                rows.forEach(row => {
                    if (row.cells.length <= 1) return; // Skip "No equipment registered" row
                    const equipName = row.cells[0].textContent.toLowerCase();
                    
                    // Show row if it matches both filter and search
                    const matchesFilter = filterValue === 'all' || equipName.includes(filterValue);
                    const matchesSearch = searchTerm === '' || equipName.includes(searchTerm);
                    row.style.display = (matchesFilter && matchesSearch) ? '' : 'none';
                });
            }

            // Add event listeners
            if (searchInput && filterSelect) {
                searchInput.addEventListener('input', filterTable);
                filterSelect.addEventListener('change', filterTable);
            }
        });

        // Model dropdown functionality
        document.addEventListener('DOMContentLoaded', function() {
            const equipmentTypeSelect = document.getElementById('equipment_type');
            const modelNameSelect = document.getElementById('model_name');

            if (!equipmentTypeSelect || !modelNameSelect) return;

            equipmentTypeSelect.addEventListener('change', function() {
                const equipTypeId = this.value;
                
                // Clear existing options
                while (modelNameSelect.firstChild) {
                    modelNameSelect.removeChild(modelNameSelect.firstChild);
                }

                // Add default option
                const defaultOption = document.createElement('option');
                defaultOption.value = '';
                defaultOption.textContent = 'Brand/Model Name';
                defaultOption.disabled = true;
                defaultOption.selected = true;
                modelNameSelect.appendChild(defaultOption);

                if (equipTypeId) {
                    fetch(`?equip_type_id=${equipTypeId}`)
                        .then(response => response.json())
                        .then(data => {
                            // Use Set for unique models
                            const uniqueModels = new Map();
                            data.forEach(model => {
                                if (!uniqueModels.has(model.model_id)) {
                                    uniqueModels.set(model.model_id, model);
                                }
                            });

                            uniqueModels.forEach(model => {
                                const option = document.createElement('option');
                                option.value = model.model_id;
                                option.textContent = model.model_name;
                                modelNameSelect.appendChild(option);
                            });
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            const errorOption = document.createElement('option');
                            errorOption.value = '';
                            errorOption.textContent = 'Error loading models';
                            errorOption.disabled = true;
                            modelNameSelect.appendChild(errorOption);
                        });
                }
            });
        });
    </script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Equipment Name Validation
        const equipNameInput = document.getElementById('equip_name');
        equipNameInput.addEventListener('input', function () {
            let value = this.value;

            // Allow only letters, spaces, and hyphens
            value = value.replace(/[^a-zA-Z\s-]/g, '');

            // Prevent consecutive spaces or hyphens
            value = value.replace(/(\s{2,}|\-{2,})/g, ' ');

            // Auto-capitalize the first letter of each word
            value = value.replace(/\b\w/g, (char) => char.toUpperCase());

            // Restrict length to 50 characters
            this.value = value.slice(0, 50);
        });

        // Property Number Validation
        const propertyNumInput = document.getElementById('property_num');
        propertyNumInput.addEventListener('input', function () {
            let value = this.value;

            // Allow letters, numbers, dashes, and slashes
            value = value.replace(/[^a-zA-Z0-9\/-]/g, '');

            // Prevent consecutive dashes or slashes
            value = value.replace(/(\/{2,}|\-{2,})/g, '-');

            // Auto-capitalize letters
            value = value.toUpperCase();

            // Restrict length to 30 characters
            this.value = value.slice(0, 30);
        });
    });
</script>

</body>
</html>
