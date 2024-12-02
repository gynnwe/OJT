<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ICTEMMS</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Material Icons -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded" />
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="styles.css">
</head>
<body class="dashboard">
    <!-- Header-->
    <div class="header">
        <span id="current-page">Dashboard</span>
        <div class="user-info">
            <span class="material-symbols-rounded">account_box</span>
            <div class="text-info">
                <span class="username"><?php echo ucfirst(htmlspecialchars($_SESSION['firstname'])) . ' ' . ucfirst(htmlspecialchars($_SESSION['lastname']));?></span>
                <span class="role"><?php echo ucfirst(htmlspecialchars($_SESSION['role']));?></span>
            </div>
        </div>
    </div>
    
    <div class="main-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="assets/usep-logo.png" alt="Logo" class="logo">
            </div>
            <ul class="nav-links">
                <li><a href="dashboard-content.php" class="nav-link active" data-title="Dashboard"><span class="material-symbols-rounded">home</span>Dashboard</a></li>
                <?php if ($_SESSION['role'] !== 'Assistant'): ?>
                <hr>
                <p style="color: #343a40"> Management</p>
                <li><a href="add_users.php" class="nav-link" data-title="User Management"><span class="material-symbols-rounded">person_add</span>Users</a></li>
                <li><a href="add_equipment_type.php" class="nav-link" data-title="Equipment Type Management"><span class="material-symbols-rounded">devices</span>Equipment Type</a></li>
                <li><a href="add_model.php" class="nav-link" data-title="Equipment Model Management"><span class="material-symbols-rounded">dvr</span>Model</a></li>
                <li><a href="add_location.php" class="nav-link" data-title="Location Management"><span class="material-symbols-rounded">add_location_alt</span>Location</a></li>
                <li><a href="add_remarks.php" class="nav-link" data-title="Remarks Management"><span class="material-symbols-rounded">edit_square</span>Remarks</a></li>
                <li><a href="add_personnel.php" class="nav-link" data-title="Personnel Info Management"><span class="material-symbols-rounded">groups_2</span>Personnel</a></li>
                <hr>
                <?php endif; ?>
                <li><a href="equipment_input_ict.php" class="nav-link" data-title="Equipment Registration"><span class="material-symbols-rounded">add_box</span>Equipment Registration</a></li>
                <li><a href="plan_maintenance.php" class="nav-link" data-title="Plan Maintenance"><span class="material-symbols-rounded">contract_edit</span>Plan Maintenance</a></li>
                <li><a href="equipment_maintenance.php" class="nav-link" data-title="Equipment Maintenance"><span class="material-symbols-rounded">build</span>Equipment Maintenance</a></li>
                <li><a href="reports.php" class="nav-link" data-title="Reports"><span class="material-symbols-rounded">report</span>Reports</a></li>
                <li><a href="#" onclick="logout()" class="nav-link" data-title="Logout"><span class="material-symbols-rounded">logout</span>Logout</a></li>
            </ul>
        </div>

        <!-- Main Content Area -->
        <div class="main-content">
            <iframe id="content-frame" class="content-frame" src="dashboard-content.php"></iframe>
			
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle navigation clicks
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                if (this.getAttribute('href') !== '#') {
                    e.preventDefault();
                    
                    // Update active state
                    document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Update page title
                    document.getElementById('current-page').textContent = this.dataset.title;
                    
                    // Load content in iframe
                    document.getElementById('content-frame').src = this.getAttribute('href');
                }
            });
        });
    });

    function logout() {
        if (confirm('Are you sure you want to logout?')) {
            window.location.href = 'logout.php';
        }
    }
    </script>
</body>
</html>