<?php
session_start();

$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "ictmms";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $pass = $_POST['psw'];

    try {
        // Create connection
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check in the admin table first
        $sql = "SELECT * FROM admin WHERE email = :email";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        // If admin exists, verify the password
        if ($admin && password_verify($pass, $admin['password'])) {
            // Set session variables for admin
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $admin['username'];
            $_SESSION['firstname'] = $admin['firstname'];
            $_SESSION['lastname'] = $admin['lastname'];
            $_SESSION['email'] = $admin['email'];
            $_SESSION['role'] = 'admin'; // Set role as admin

            header("location: dashboard.php"); // Redirect to the dashboard or home page
            exit; // Make sure to exit after redirection
        } 

        // Check in the user table if not admin
        $sql = "SELECT * FROM user WHERE email = :email";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // If user exists, verify the password
        if ($user && password_verify($pass, $user['password'])) {
            // Set session variables for user
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $user['username'];
            $_SESSION['firstname'] = $user['firstname'];
            $_SESSION['lastname'] = $user['lastname'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = 'user'; // Set role as user

            header("location: dashboard.php"); // Redirect to the dashboard or home page
            exit; // Make sure to exit after redirection
        } 

        // Invalid email or password for both admin and user
        echo "Invalid email or password.";
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
    }

    $conn = null;
}
?>
