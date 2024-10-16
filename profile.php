<?php
session_start();

$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "ictmms";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login if not logged in
    exit();
}

$user_id = $_SESSION['user_id'];
$conn = null;

try {
    // Create connection
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch user details
    $sql = "SELECT email, firstname, lastname FROM user WHERE admin_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        die("User not found.");
    }

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_email = $_POST['email'];
    $new_firstname = $_POST['firstname'];
    $new_lastname = $_POST['lastname'];
    $new_password = $_POST['password'];
    $new_password_repeat = $_POST['password_repeat'];

    // Validate password match
    if ($new_password !== $new_password_repeat) {
        $error_message = "Passwords do not match.";
    } else {
        // Hash the new password if provided
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $sql = "UPDATE user SET email = :email, firstname = :firstname, lastname = :lastname, password = :password WHERE admin_id = :user_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':password', $hashed_password);
        } else {
            // Update without changing the password
            $sql = "UPDATE user SET email = :email, firstname = :firstname, lastname = :lastname WHERE admin_id = :user_id";
            $stmt = $conn->prepare($sql);
        }

        $stmt->bindParam(':email', $new_email);
        $stmt->bindParam(':firstname', $new_firstname);
        $stmt->bindParam(':lastname', $new_lastname);
        $stmt->bindParam(':user_id', $user_id);

        if ($stmt->execute()) {
            $success_message = "Profile updated successfully!";
        } else {
            $error_message = "Profile update failed.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css"> <!-- Include your CSS file -->
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Update Profile</h2>
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="profile.php" class="needs-validation" novalidate>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                <div class="invalid-feedback">Please enter a valid email.</div>
            </div>
            
            <div class="form-group">
                <label for="firstname">First Name:</label>
                <input type="text" id="firstname" name="firstname" class="form-control" value="<?php echo htmlspecialchars($user['firstname']); ?>" required>
                <div class="invalid-feedback">Please enter your first name.</div>
            </div>
            
            <div class="form-group">
                <label for="lastname">Last Name:</label>
                <input type="text" id="lastname" name="lastname" class="form-control" value="<?php echo htmlspecialchars($user['lastname']); ?>" required>
                <div class="invalid-feedback">Please enter your last name.</div>
            </div>
            
            <div class="form-group">
                <label for="password">New Password:</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Leave blank if not changing">
            </div>
            
            <div class="form-group">
                <label for="password_repeat">Repeat New Password:</label>
                <input type="password" id="password_repeat" name="password_repeat" class="form-control" placeholder="Leave blank if not changing">
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Update Profile</button>
        </form>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Example of Bootstrap validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
    </script>
</body>
</html>
