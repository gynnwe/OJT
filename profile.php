<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ictmms";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Create connection
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch user details
    $sql = "SELECT * FROM user WHERE admin_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        die("User not found.");
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['status' => 'error', 'message' => ''];
    $changes_made = false;
    $profile_updated = false;
    $password_updated = false;
    
    // Get user data
    $stmt = $conn->prepare("SELECT * FROM user WHERE admin_id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch();
    
    // Handle profile updates (email and name)
    $email = trim($_POST['email']);
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    
    if ($email !== $user['email'] || $firstname !== $user['firstname'] || $lastname !== $user['lastname']) {
        // Validate email if it changed
        if ($email !== $user['email']) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response['message'] = 'Invalid email format';
                echo json_encode($response);
                exit;
            }
            
            // Check if email is already taken
            $stmt = $conn->prepare("SELECT COUNT(*) FROM user WHERE email = ? AND admin_id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetchColumn() > 0) {
                $response['message'] = 'Email is already taken';
                echo json_encode($response);
                exit;
            }
        }
        
        // Update profile information
        $stmt = $conn->prepare("UPDATE user SET email = ?, firstname = ?, lastname = ? WHERE admin_id = ?");
        $stmt->execute([$email, $firstname, $lastname, $user_id]);
        $changes_made = true;
        $profile_updated = true;
    }
    
    // Handle password update if password fields are filled
    $current_password = trim($_POST['current_password'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $repeat_password = trim($_POST['repeat_password'] ?? '');
    
    if ($current_password || $new_password || $repeat_password) {
        // Verify all password fields are filled
        if (!$current_password || !$new_password || !$repeat_password) {
            $response['message'] = 'All password fields are required when changing password';
            echo json_encode($response);
            exit;
        }
        
        // Verify current password
        if (!password_verify($current_password, $user['password'])) {
            $response['message'] = 'Current password is incorrect';
            echo json_encode($response);
            exit;
        }
        
        // Verify new passwords match
        if ($new_password !== $repeat_password) {
            $response['message'] = 'New passwords do not match';
            echo json_encode($response);
            exit;
        }
        
        // Validate password strength
        if (strlen($new_password) < 8 || 
            !preg_match('/[A-Z]/', $new_password) || 
            !preg_match('/[0-9]/', $new_password) || 
            !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $new_password)) {
            $response['message'] = 'Password must be at least 8 characters long with at least 1 uppercase letter, number and symbol';
            echo json_encode($response);
            exit;
        }
        
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE user SET password = ? WHERE admin_id = ?");
        $stmt->execute([$hashed_password, $user_id]);
        $changes_made = true;
        $password_updated = true;
    }
    
    if ($changes_made) {
        $response['status'] = 'success';
        
        // Create specific success message based on what was updated
        if ($profile_updated && $password_updated) {
            $response['message'] = 'Profile information and password updated successfully';
        } else if ($profile_updated) {
            $response['message'] = 'Profile information updated successfully';
        } else if ($password_updated) {
            $response['message'] = 'Password updated successfully';
        }
        
        if ($profile_updated) {
            $response['user'] = [
                'firstname' => $firstname,
                'lastname' => $lastname,
                'email' => $email
            ];
        }
    } else {
        $response['message'] = 'No changes were made';
    }
    
    echo json_encode($response);
    exit;
}
?>
