<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'Admin') {
    header("location: index.php");
    exit;
}

include 'conn.php';

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (isset($_POST['deleted_id'])) {
            $delete_id = $_POST['deleted_id'];
            $softDeleteSQL = "UPDATE user SET deleted_id = 1 WHERE admin_id = :deleted_id AND role != 'Admin'";
            $stmt = $conn->prepare($softDeleteSQL);
            $stmt->bindParam(':deleted_id', $delete_id);
            $stmt->execute();
            $stmt->execute();
            echo "Success";
            exit;
        }

        if (isset($_POST['email'])) {
            $email = $_POST['email'];
            $firstname = $_POST['firstname'];
            $lastname = $_POST['lastname'];
            $user = $_POST['username'];
            $pass = $_POST['psw'];
            $pass_repeat = $_POST['psw-repeat'];
            $role = 'Assistant';
            $user_id = isset($_POST['user_id']) ? $_POST['user_id'] : null;

            if ($pass !== $pass_repeat) {
                $error = "Passwords do not match.";
            } else {
                // Check if email already exists (excluding the current user if editing)
                $checkSQL = "SELECT COUNT(*) FROM user WHERE email = :email AND deleted_id = 0 AND (:user_id IS NULL OR admin_id != :user_id)";
                $stmt = $conn->prepare($checkSQL);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();
                $count = $stmt->fetchColumn();

                if ($count > 0) {
                    $error = "Email already exists.";
                } else {
                    if ($user_id) {
                        // Update existing user
                        if (!empty($pass)) {
                            // If password is provided, update it too
                            $hashed_password = password_hash($pass, PASSWORD_DEFAULT);
                            $sql = "UPDATE user SET email = :email, firstname = :firstname, lastname = :lastname, 
                                   username = :username, password = :password WHERE admin_id = :user_id";
                        } else {
                            // If no password provided, keep the existing one
                            $sql = "UPDATE user SET email = :email, firstname = :firstname, lastname = :lastname, 
                                   username = :username WHERE admin_id = :user_id";
                        }
                        
                        $stmt = $conn->prepare($sql);
                        $stmt->bindParam(':email', $email);
                        $stmt->bindParam(':firstname', $firstname);
                        $stmt->bindParam(':lastname', $lastname);
                        $stmt->bindParam(':username', $user);
                        $stmt->bindParam(':user_id', $user_id);
                        if (!empty($pass)) {
                            $stmt->bindParam(':password', $hashed_password);
                        }
                    } else {
                        // Insert new user
                        $hashed_password = password_hash($pass, PASSWORD_DEFAULT);
                        $sql = "INSERT INTO user (email, firstname, lastname, username, password, role, deleted_id) 
                               VALUES (:email, :firstname, :lastname, :username, :password, :role, 0)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bindParam(':email', $email);
                        $stmt->bindParam(':firstname', $firstname);
                        $stmt->bindParam(':lastname', $lastname);
                        $stmt->bindParam(':username', $user);
                        $stmt->bindParam(':password', $hashed_password);
                        $stmt->bindParam(':role', $role);
                    }

                    if ($stmt->execute()) {
                        $_SESSION['message'] = $user_id ? "User updated successfully!" : "Registration successful!";
                        header("Location: " . $_SERVER['PHP_SELF']);
                        exit;
                    } else {
                        $error = "Error " . ($user_id ? "updating" : "registering") . " user.";
                    }
                }
            }
        }
    }

    // Fetch users
    $sql = "SELECT * FROM user WHERE deleted_id = 0 OR deleted_id IS NULL 
            ORDER BY CASE WHEN role = 'Admin' THEN 0 ELSE 1 END, admin_id ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debug information
    if (empty($users)) {
        echo "<!-- Debug: No users found in query -->";
        // Check if any users exist at all
        $checkSql = "SELECT COUNT(*) FROM user";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->execute();
        $totalUsers = $checkStmt->fetchColumn();
        echo "<!-- Debug: Total users in database: " . $totalUsers . " -->";
    }

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
			padding: 11px 10px !important;
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
			margin-top: 3.5px;
			height: 42px;
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
			padding: 2px 8px; 
		}
		
		.pagination .page-link:hover {
			color: #b86e63 !important;
		}

		.page-link {
			color: #474747 !important; }
		
		.table th, .table td {
			vertical-align: middle !important;
		}
		
		.table th:nth-child(1),
		.table td:nth-child(1) {
			max-width: 200px;
			overflow: hidden;
			text-overflow: ellipsis;
			white-space: nowrap;
		}
		
		.table th:nth-child(2), 
		.table td:nth-child(2),
		.table th:nth-child(3), 
		.table td:nth-child(3) {
			width: 20%;
		}
		
		.table th:nth-child(4), 
		.table td:nth-child(4) {
			width: 15%;
		}
		
		.table th:nth-child(5), 
		.table td:nth-child(5) {
			width: 100px;
			text-align: center;
		}

		.action-buttons {
			white-space: nowrap;
		}

		.action-buttons img {
			width: 20px;
			cursor: pointer;
			margin: 0 3px;
		}

		.modal-content {
			background-color: #ffffff;
			border-radius: 24px !important;
			box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1) !important;
			border: none !important;
			padding: 15px;
			max-width: 400px;
			margin: auto;
			margin-top: 160px;
		}

		.modal-header {
			border-bottom: none;
			margin-top: -15px;
			padding: 15px;
		}

		.modal-header h5 {
			color: #3A3A3A;
			font-weight: bold;
			font-size: 13px;
			padding-top: 4px;
		}

		.modal-body {
			padding: 20px;
		}

		.modal-footer {
			border-top: none;
		}

		.modal-body p {
			margin-bottom: 10px;
			font-size: 12px;
		}

		.modal-body ul {
			padding-left: 20px; 
			margin-bottom: 0px;
		}

		.section-divider {
			border: none;
			height: 1px;
			background-color: #ddd;
			margin-top: 5px; 
			margin-bottom: 10px;
		}

		.modal-footer button {
			width: 130px; 
			height: 33px;
			background-color: #a81519;
			color: white; 
			font-weight: bold; 
			font-size: 12px;
			border: none;
			border-radius: 14px;
			margin-top: 0px;
			margin-bottom: -13px;
		}

		.modal-footer button:hover {
			background-color: #E3595C;
		}
		
		.modal-body ul li {
			color: #dc3545;
			font-size: 12px;
			margin-left: 40px;
		}
	</style>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
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
                <input type="hidden" name="user_id" id="user_id">
                <input type="email" placeholder="Enter e-mail" name="email" id="email" required>
                <input type="text" placeholder="Enter your first name" name="firstname" id="firstname" required>
                <input type="text" placeholder="Enter your last name" name="lastname" id="lastname" required>
                <input type="text" placeholder="Enter your username" name="username" id="username" required>
                <div class="password-container">
                    <input type="password" placeholder="Enter password" name="psw" id="psw" required>
                    <i class="bi bi-eye password-toggle" id="togglePassword"></i>
                </div>
                <div class="password-container">
                    <input type="password" placeholder="Repeat password" name="psw-repeat" id="psw-repeat" required>
                    <i class="bi bi-eye password-toggle" id="toggleRepeatPassword"></i>
                </div>
                <hr class="section-divider2">
                <button type="submit" class="registerbtn" id="submitBtn">Register</button>
            </form>
        </div>
	
		<div class="card search-card">
            <h1>List of Registered Users</h1>
            <hr class="section-divider3">
            <div class="form-inline">
                <select id="filterBy" class="form-control mr-2">
                    <option value="email">Email</option>
                    <option value="firstname">First Name</option>
                    <option value="lastname">Last Name</option>
                    <option value="username">Username</option>
                </select>
                <input type="text" id="searchInput" class="form-control" placeholder="Search...">
            </div>

            <p class="user-title">Existing Registered Users</p>
            <div class="table-responsive">
                
				
				
				<?php
				$maxRows = 7; 
				$totalEntries = count($users);
				$totalPages = ceil($totalEntries / $maxRows);

				$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
				$currentPage = max(1, min($currentPage, $totalPages)); 

				$startIndex = ($currentPage - 1) * $maxRows;

				$currentUsers = array_slice($users, $startIndex, $maxRows);
				?>
				<table class="table table-striped">
					<thead>
						<tr>
							<th>Email</th>
							<th>First Name</th>
							<th>Last Name</th>
							<th>Username</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody id="locationTableBody">
						<?php 
						if ($totalEntries === 0): ?>
							<tr class="no-users"><td colspan="5">No users available.</td></tr>
							<?php 
							for ($j = 1; $j < $maxRows; $j++): ?>
								<tr class="empty-row"><td colspan="5"></td></tr>
							<?php endfor; 
						else:
							foreach ($currentUsers as $user): ?>
								<tr id="row-<?php echo htmlspecialchars($user['admin_id']); ?>">
									<td title="<?php echo htmlspecialchars($user['email']); ?>"><?php echo htmlspecialchars($user['email']); ?></td>
									<td><?php echo htmlspecialchars($user['firstname']); ?></td>
									<td><?php echo htmlspecialchars($user['lastname']); ?></td>
									<td><?php echo htmlspecialchars($user['username']); ?></td>
									<td class="action-buttons">
										<a href="#" onclick="editUser(<?php echo $user['admin_id']; ?>, '<?php 		echo htmlspecialchars($user['email']); ?>', 
										   '<?php echo htmlspecialchars($user['firstname']); ?>', '<?php echo htmlspecialchars($user['lastname']); ?>', 
										   '<?php echo htmlspecialchars($user['username']); ?>')">
											<img src="edit.png" alt="Edit">
										</a>
										<?php if ($user === reset($currentUsers) && $user['role'] === 'Admin'): ?>
											<a href="#" style="opacity: 0.35; pointer-events: none;">
												<img src="delete.png" alt="Delete">
											</a>
										<?php elseif ($user['role'] === 'Assistant'): ?>
											<a href="#" onclick="softDelete(<?php echo $user['admin_id']; ?>)">
												<img src="delete.png" alt="Delete">
											</a>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach;

							for ($j = count($currentUsers); $j < $maxRows; $j++): ?>
								<tr class="empty-row"><td colspan="5"></td></tr>
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

	<div class="modal fade" id="passwordErrorModal" tabindex="-1" aria-labelledby="passwordErrorModalLabel" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="passwordErrorModalLabel">Invalid Password</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<p>The password must meet the following criteria:</p>
					<ul>
						<li>8+ characters</li>
						<li>Uppercase letter</li>
						<li>Number</li>
						<li>Symbol</li>
					</ul>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
				</div>
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

				// Password validation
			$('form').on('submit', function(e) {
				const password = $('#psw').val().trim();
				const validation = validatePassword(password);

				// Check if password is valid
				if (!validation.valid) {
					e.preventDefault(); // Prevent form submission
					$('#passwordErrorModal').modal('show'); // Show the modal
					return;
				}
			});

			function validatePassword(password) {
				const minLength = password.length >= 8;
				const hasUpperCase = /[A-Z]/.test(password);
				const hasNumber = /[0-9]/.test(password);
				const hasSymbol = /[!@#$%^&*(),.?":{}|<>]/.test(password);

				return {
					valid: minLength && hasUpperCase && hasNumber && hasSymbol,
					errors: {
						length: !minLength,
						uppercase: !hasUpperCase,
						number: !hasNumber,
						symbol: !hasSymbol
					}
				};
			}
				
                // Search functionality
                $('#searchInput').on('keyup', function() {
                    const searchValue = $(this).val().toLowerCase();
                    const filterBy = $('#filterBy').val();
                    
                    $('#locationTableBody tr:not(.empty-row)').each(function() {
                        const row = $(this);
                        let text = '';
                        
                        // Get text from the appropriate column based on filter
                        switch(filterBy) {
                            case 'email':
                                text = row.find('td:eq(0)').text();
                                break;
                            case 'firstname':
                                text = row.find('td:eq(1)').text();
                                break;
                            case 'lastname':
                                text = row.find('td:eq(2)').text();
                                break;
                            case 'username':
                                text = row.find('td:eq(3)').text();
                                break;
                        }
                        
                        // Show/hide row based on search match
                        if (text.toLowerCase().includes(searchValue)) {
                            row.show();
                        } else {
                            row.hide();
                        }
                    });
                });

                // Trigger search when filter changes
                $('#filterBy').on('change', function() {
                    $('#searchInput').trigger('keyup');
                });
	        });

	        function editUser(id, email, firstname, lastname, username) {
	            document.getElementById('user_id').value = id;
	            document.getElementById('email').value = email;
	            document.getElementById('firstname').value = firstname;
	            document.getElementById('lastname').value = lastname;
	            document.getElementById('username').value = username;
	            
	            // Make password fields optional when editing
	            document.getElementById('psw').removeAttribute('required');
	            document.getElementById('psw-repeat').removeAttribute('required');
	            
	            // Change button text
	            document.getElementById('submitBtn').textContent = 'Update User';
	            
	            // Scroll to form
	            document.querySelector('.add-edit-card').scrollIntoView({ behavior: 'smooth' });
	        }

	        function softDelete(id) {
	            if (confirm('Are you sure you want to delete this user?')) {
	                $.ajax({
	                    url: 'add_users.php',
	                    type: 'POST',
	                    data: { deleted_id: id },
	                    success: function(response) {
	                        if (response.trim() === "Success") {
	                            document.getElementById('row-' + id).style.display = 'none';
	                        } else {
	                            alert('Failed to delete the user.');
	                        }
	                    }
	                });
	            }
	        }

	        // Reset form when adding new user
	        function resetForm() {
	            document.getElementById('user_id').value = '';
	            document.getElementById('psw').setAttribute('required', '');
	            document.getElementById('psw-repeat').setAttribute('required', '');
	            document.getElementById('submitBtn').textContent = 'Register';
	        }

	        // Add reset when clicking Register button in nav
	        document.addEventListener('DOMContentLoaded', function() {
	            const navLinks = document.querySelectorAll('nav a');
	            navLinks.forEach(link => {
	                if (link.textContent.includes('Register')) {
	                    link.addEventListener('click', resetForm);
	                }
	            });
	        });

	    </script>
</body>
</html>
