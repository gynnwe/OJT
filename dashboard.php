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
	<!-- MATERIAL CDN  -->
	<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded" />
	<link rel="stylesheet" href="styles.css">
	<script src="scripts.js" defer ></script>
    <title>ICTEMMS</title>
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
	
	<!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="assets/usep-logo.png" alt="Logo" class="logo">
        </div>
        <ul class="nav-links">
            <li><a href="#" class="active"onclick="loadPage('dashboard-content.php', 'Dashboard')"><span class="material-symbols-rounded">home</span>Dashboard</a></li>
			<?php if ($_SESSION['role'] !== 'Assistant'): ?>
			<hr>
			<p style="color: #343a40"> Management</p>
			<li><a href="#" onclick="loadPage('management.php', 'User Management')"><span class="material-symbols-rounded">person_add</span>Users</a></li>
			<li><a href="#" onclick="loadPage('add_equipment_type.php', 'Equipment Type Management')"><span class="material-symbols-rounded">devices</span>Equipment Type</a></li>
			<li><a href="#" onclick="loadPage('add_model.php', 'Equipment Model Management')"><span class="material-symbols-rounded">dvr</span>Model</a></li>
			<li><a href="#" onclick="loadPage('add_location.php', 'Location Management')"><span class="material-symbols-rounded">add_location_alt</span>Location</a></li>
			<li><a href="#" onclick="loadPage('add_remarks.php', 'Remarks Management')"><span class="material-symbols-rounded">edit_square</span>Remarks</a></li>
			<li><a href="#" onclick="loadPage('add_personnel.php', 'Personnel Info Management')"><span class="material-symbols-rounded">groups_2</span>Personnel</a></li>
			<hr>
			<?php endif; ?>
            <li><a href="#" onclick="loadPage('equipment_input_ict.php', 'Equipment Registration')"><span class="material-symbols-rounded">add_box</span>Equipment Registration</a></li>
            <li><a href="#" onclick="loadPage('equipment_maintenance.php', 'Equipment Maintenance')"><span class="material-symbols-rounded">build</span>Equipment Maintenance </a></li>
            <li><a href="#" onclick="loadPage('plan_maintenance.php', 'Plan Maintenance')"><span class="material-symbols-rounded">contract_edit</span>Plan Maintenance</a></li>
            <li><a href="#" onclick="loadPage('reports.php', 'Reports')"><span class="material-symbols-rounded">report</span>Reports</a></li>
			<li><a href="#" onclick="logout()"><span class="material-symbols-rounded">logout</span>Logout</a></li>
        </ul>
    </div>

    <!-- Main Content Area -->
    <div class="main-content">
        <div id="content-area">
		<!-- -->
        </div>
    </div>
</body>
</html>
