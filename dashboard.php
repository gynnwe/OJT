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
    <title>Dashboard</title>
</head>
<body>

<h1>Welcome, <?php echo ucfirst(htmlspecialchars($_SESSION['username'])); ?>!</h1>

<!-- Side Bar Menu for Dashboard -->
    <div class="menu-bar" style="position:fixed; height: 100%; width: 250px; left:0; background-color: lightgrey;">	<!-- inline css for area checking-->
        <div class="menu">
            <ul class="menu-links">
            
                <li class="nav-link">
                    <a href="dashboard.php">
                    <i class=""></i> <!-- Add Icon Here -->
                    <span class="text nav-text">Dashboard</span>
                    </a>
                </li>
                
                <li class="nav-link">
                    <a href="equipment_input_ict.php">
                    <i class=""></i> <!-- Add Icon Here -->
                    <span class="text nav-text">ICT Equipment</span>
                    </a>
                </li>
                
                <li class="nav-link">
                <a href="equipment_input_office.php">
                    <i class=''></i> <!-- Add Icon Here -->
                    <span class="text nav-text">Facility Building</span>
                    </a>
                </li>
                
                <li class="nav-link">
                <a href="equipment_input.php">
                    <i class=""></i> <!-- Add Icon Here -->
                    <span class="text nav-text">Reports</span>
                    </a>
                </li>
                
                <div class="account-settings">
                    <p>Account Settings</p>
                    
                    <li class="Profile-btn">
                        <a href="#">
                            <i class=""></i> <!-- Add Icon Here -->
                            <span class="text nav-text">Profile</span>
                        </a>
                    </li>
                    
                    <li class="logout-btn">
                        <a href="logout.php">
                            <i class=""></i> <!-- Add Icon Here -->
                            <span class="text nav-text">Logout</span>
                        </a>
                    </li>
                </div>
            </ul>
        </div>
    </div>
	
	<!-- Right side content part of dashboard-->
	
	<main class="" style=" position: absolute;top: 0; left: 250px; height: 100vh; width: calc(100% - 250px); background-color: grey;"> <!-- inline css for area checking-->
	
	
	</main>
</body>
</html>
