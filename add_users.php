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

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Handle form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $email = $_POST['email'];
        $firstname = $_POST['firstname'];
        $lastname = $_POST['lastname'];
        $user = $_POST['username'];
        $pass = $_POST['psw'];
        $pass_repeat = $_POST['psw-repeat'];
        $role = 'Assistant';

        if ($pass !== $pass_repeat) {
            $error = "Passwords do not match.";
        } else {
            $hashed_password = password_hash($pass, PASSWORD_DEFAULT);

            $sql = "INSERT INTO user (email, firstname, lastname, username, password, role) 
                   VALUES (:email, :firstname, :lastname, :username, :password, :role)";
            $stmt = $conn->prepare($sql);

            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':firstname', $firstname);
            $stmt->bindParam(':lastname', $lastname);
            $stmt->bindParam(':username', $user);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':role', $role);

            if ($stmt->execute()) {
                $_SESSION['message'] = "Registration successful!";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                $error = "Error registering user.";
            }
        }
    }

    // Fetch users
    $sql = "SELECT * FROM user";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}

// Store any success/error messages
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
    <title>Add Users</title>
	<style>
		@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap');

		body {
			font-family: Arial, sans-serif;
			background-color: transparent !important;
		}
		.container {
			margin-top: 3.65rem !important;
			margin-left: 2.6rem !important;
			display: flex;
		}
		.card {
			background-color: #ffffff;
			border-radius: 24px !important;
			box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
			margin-bottom: 20px;
			padding: 15px;
			border: none;
			height: 546px;
		}
		.add-edit-card {
			width: 30%;
			position: relative !important;
		}
		
		.alert {
			position: absolute !important;
			font-size: 10px !important;
			top: 0 !important;
			right: 0 !important;
			max-width: 170px !important;
		}

		.search-card {
			width: 70%;
			margin-left: 20px;
		}
		
		
		h1 {
			color: #3A3A3A;
			font-weight: bold !important;
			font-size: 13px !important;
		}
		.container p {
			color: #3A3A3A;
			font-weight: regular;
			font-size: 13px !important;
			margin-bottom: 10px !important;
		}		
		.user-title {
    		margin-top: 15px;
		}
		hr {
			border: none;
            height: 0.1px;
            background-color: #ddd;
		}
		
		.section-divider1 {
            margin-top: 16px;
			margin-bottom: 18px;
		}
		
		.section-divider2 {
            margin-top: -3px;
			margin-bottom: 18px;
		}
		.section-divider3 {
            margin-top: 8px;
			margin-bottom: 17px;
		}
	
		.container input[type="email"],
        .container input[type="password"],
        .container input[type="text"],
		.password-container input {
			width: 100%;
			border: 2px solid #646464; 
			border-radius: 14px; 
			color: #646464; 
			font-size: 12px;
			padding: 12px 15px;
			margin-bottom: 20px;
        }
		
		.password-container input {
			margin-top: 0px;
		}
		
		.password-container {
            position: relative;
			margin-top: 0px;
			padding: 0px;
        }
		
		.password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 18px;
            color: #666;
        }
		
		.add-edit-card a {
			color: #a81519 !important;
			font-weight: bold;
		}
		
		.add-edit-card a:hover {
			color: #E3595C !important;
        }
		button.registerbtn {
            width: 100%;
            padding: 12px;
            background-color: #a81519;
			color: white; 
			font-weight: bold; 
			font-size: 12px; 
			border: none; 
			border-radius: 14px;
            cursor: pointer;
			padding-top: 12px;
			padding-bottom: 13px;
			margin-bottom: 10px;
        }

        button.registerbtn:hover {
            background-color: #8b0000;
        }
		
		.form-control {
			width: 257px !important;
			height: 33px !important;
			border: 2px solid #646464 !important;
			border-radius: 14px !important; 
			color: #646464 !important; 
			font-size: 12px !important;
			margin: 0px !important;
		}
		
		.mr-2 {
			background-color: #d1d1d1 !important;
			border: none !important;
			color: #646464 !important;
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
			color:#646464;
			border: none;
			display: inline-block;
			margin-top: -6px;
			margin-bottom: -2px;
		}
		
		.table thead th {
			border-bottom: none !important;}

		.table th:nth-child(1) {
			width: 35%; 
		}
		.table th:nth-child(2) {
			width: 20%; 
		}
		.table th:nth-child(3) {
			width: 20%; 
		}
		.table th:nth-child(4) {
			width: 25%; 
		}

		.table td {
			color:#646464 ; 
			font-weight :bold ;
			font-size: 10px;
			border-collapse: separate; 
			border-spacing: 10px 40px;
			border: none !important; 
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
			width: 35%;
		}

		td:nth-child(2) {
			width: 20%; 
		}

		td:nth-child(3) {
			width: 20%;

		}

		td:nth-child(4) {
			width: 20%;
		}

		table tbody {
			border-spacing: 15px 155px;
			border-radius: 14px; 
			margin: 20 -20px;
		}

		.table tbody tr:nth-child(odd), .table tbody tr:nth-child(even) {
			background-color: white !important;
			border: 1px solid #DFDFDF;
			border-radius: 14px; 
			display: block;
			width: 100%;
			margin-top: 5px;
		}

		.table tbody tr:hover {
			background-color :#ebebeb !important; 
		}

		tr {
			font-size: 13px;
		}
		.empty-row, .no-users {
			height: 40px;
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
			color: #474747 !important; }
	</style>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</head>
<body>
	<div class="container">
        <div class="card add-edit-card">
            <?php if (isset($message)) echo "<div class='alert alert-success floating-alert' id='successAlert'>$message</div>"; ?>
            <?php if (isset($error)) echo "<div class='alert alert-danger floating-alert' id='errorAlert'>$error</div>"; ?>
            
            <form method="POST">
                <h1>Register a User</h1>
                <hr class="section-divider1">

                <input type="email" placeholder="Enter e-mail" name="email" id="email" required>
                <input type="text" placeholder="Enter your first name" name="firstname" id="firstname" required>
                <input type="text" placeholder="Enter your last name" name="lastname" id="lastname" required>
                <input type="text" placeholder="Enter username" name="username" id="username" required>

                <div class="password-container">
                    <input type="password" placeholder="Enter Password" name="psw" id="psw" required>
                    <i class="bi bi-eye password-toggle" id="togglePassword"></i>
                </div>

                <div class="password-container">
                    <input type="password" placeholder="Repeat Password" name="psw-repeat" id="psw-repeat" required>
                    <i class="bi bi-eye password-toggle" id="toggleRepeatPassword"></i>
                </div>
                <hr class="section-divider2">

                <button type="submit" class="registerbtn">Register</button>
            </form>
        </div>
	
		<div class="card search-card">
            <h1>List of Registered Users</h1>
            <hr class="section-divider3">
            <div class="form-inline">
                <select id="filterBy" class="form-control mr-2">
                    <option value="id">Email</option>
                    <option value="building">First Name</option>
                    <option value="office">Last Name</option>
                    <option value="room">Username</option>
                </select>
                <input type="text" id="searchInput" class="form-control" placeholder="Search...">
            </div>

            <p class="user-title">Existing Registered Users</p>
            <div class="table-responsive">
                
				
				
				<?php
				// Assuming $users is your array of users
				$maxRows = 7; // Maximum rows per page
				$totalEntries = count($users); // Total number of entries
				$totalPages = ceil($totalEntries / $maxRows); // Total number of pages

				// Get the current page from query parameters, default to 1
				$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
				$currentPage = max(1, min($currentPage, $totalPages)); // Ensure current page is within valid range

				// Calculate the starting index for the current page
				$startIndex = ($currentPage - 1) * $maxRows;

				// Slice the users array to get only the entries for the current page
				$currentUsers = array_slice($users, $startIndex, $maxRows);
				?>

				<table class="table table-striped">
					<thead>
						<tr>
							<th>Email</th>
							<th>First Name</th>
							<th>Last Name</th>
							<th>Username</th>
						</tr>
					</thead>
					<tbody id="locationTableBody">
						<?php 
						if ($totalEntries === 0): ?>
							<tr class="no-users"><td colspan="4">No users available.</td></tr>
							<?php 
							// Add empty rows to make a total of 7
							for ($j = 1; $j < $maxRows; $j++): ?>
								<tr class="empty-row"><td colspan="4"></td></tr>
							<?php endfor; 
						else:
							// Display users for the current page
							foreach ($currentUsers as $user): ?>
								<tr id="row-<?php echo htmlspecialchars($user['email']); ?>">
									<td><?php echo htmlspecialchars($user['email']); ?></td>
									<td><?php echo htmlspecialchars($user['firstname']); ?></td>
									<td><?php echo htmlspecialchars($user['lastname']); ?></td>
									<td><?php echo htmlspecialchars($user['username']); ?></td>
								</tr>
							<?php endforeach;

							// Add empty rows if there are fewer than 7 entries on this page
							for ($j = count($currentUsers); $j < $maxRows; $j++): ?>
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
	 </script>
</body>
</html>
