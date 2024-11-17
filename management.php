<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'Admin') {
    header("location: login.php");
    exit;
}

$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "ictmms";

// Create connection
$conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Fetch users
try {
    $sql = "SELECT * FROM user";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Fetch non-serviceable equipment
try {
    $sql = "SELECT e.equipment_id, e.property_num, e.status, l.building, l.office, l.room 
            FROM equipment e 
            JOIN location l ON e.location_id = l.location_id 
            WHERE e.status = 'Non-serviceable'";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $nonServiceableEquipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

$conn = null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Management</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</head>
<body>
    <div class="container mt-5">
        <h1>Management Page</h1>
        <p>Click the button below to add a new user:</p>
        <a href="registration.php" class="btn btn-primary">Add a User</a>

        <hr>
        
        <button type="button" class="btn btn-info" data-toggle="collapse" data-target="#userList">Display User List</button>

        <div id="userList" class="collapse mt-3">
            <h5>Users in the System:</h5>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Username</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['firstname']); ?></td>
                                <td><?php echo htmlspecialchars($user['lastname']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">No users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <hr>

        <!-- Archive Section -->
        <button type="button" class="btn btn-warning" data-toggle="collapse" data-target="#archiveSection">View Non-Serviceable Equipment</button>
        <div id="archiveSection" class="collapse mt-3">
            <h5>Non-Serviceable Equipment:</h5>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Equipment ID</th>
                        <th>Property Number</th>
                        <th>Status</th>
                        <th>Building</th>
                        <th>Office</th>
                        <th>Room</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($nonServiceableEquipment)): ?>
                        <?php foreach ($nonServiceableEquipment as $equipment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($equipment['equipment_id']); ?></td>
                                <td><?php echo htmlspecialchars($equipment['property_num']); ?></td>
                                <td><?php echo htmlspecialchars($equipment['status']); ?></td>
                                <td><?php echo htmlspecialchars($equipment['building']); ?></td>
                                <td><?php echo htmlspecialchars($equipment['office']); ?></td>
                                <td><?php echo htmlspecialchars($equipment['room']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">No non-serviceable equipment found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- New buttons added -->
        <div class="row mt-4">
            <div class="col-md-4">
                <button class="btn btn-primary btn-block" onclick="window.location.href='add_equipment_type.php'">Add Equipment Type</button>
            </div>
            <div class="col-md-4">
                <button class="btn btn-primary btn-block" onclick="window.location.href='add_model.php'">Add Model</button>
            </div>
            <div class="col-md-4">
                <button class="btn btn-primary btn-block" onclick="window.location.href='add_location.php'">Add Location</button>
            </div>        
            <div class="col-md-4">
                <br> <button class="btn btn-primary btn-block" onclick="window.location.href='add_remarks.php'">Add Remarks</button>
            </div>
            <div class="col-md-4">
                <br> <button class="btn btn-primary btn-block" onclick="window.location.href='add_personnel.php'">Add Personnel</button>
            </div>
        </div>
    </div>
</body>
</html>
